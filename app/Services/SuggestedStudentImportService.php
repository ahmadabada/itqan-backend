<?php

namespace App\Services;

use App\Models\SuggestedStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

/**
 * Imports the suggested-students autocomplete list from xlsx/xls/csv.
 *
 * BR-SS-2 / BR-SS-3:
 *   • Every upload truncates the table then inserts the new rows — there is no
 *     upsert and duplicates are allowed.
 *   • Validation is all-or-nothing: if any row fails, NOTHING is written; the
 *     admin gets a row-by-row error report instead of a half-imported table.
 *
 * Every import (success or failure) is archived under
 * `storage/app/private/imports/suggested-students/{YYYY-MM-DD}/`. If validation
 * fails, an `.errors.csv` companion is written next to the original so the
 * admin can fix and re-upload.
 *
 * Expected columns (header row required, order flexible by header text):
 *   national_id | first_name | second_name | third_name | family_name |
 *   student_zone | gender | is_recite_before
 */
class SuggestedStudentImportService
{
    private const STORAGE_DISK = 'local';
    private const STORAGE_DIR  = 'imports/suggested-students';

    // Title-case to match the existing students.student_zone enum.
    private const ZONES = ['East Gaza', 'West Gaza', 'North Gaza', 'South Gaza'];

    private const HEADER_ALIASES = [
        'national_id'      => ['national_id', 'رقم الهوية', 'الهوية'],
        'first_name'       => ['first_name', 'الاسم الأول', 'الاسم'],
        'second_name'      => ['second_name', 'اسم الأب', 'الاسم الثاني'],
        'third_name'       => ['third_name', 'اسم الجد', 'الاسم الثالث'],
        'family_name'      => ['family_name', 'اسم العائلة', 'العائلة'],
        'student_zone'     => ['student_zone', 'المنطقة', 'منطقة الطالب'],
        'gender'           => ['gender', 'الجنس'],
        'is_recite_before' => ['is_recite_before', 'سبق التسميع', 'سبق له التسميع'],
    ];

    private const REQUIRED_FIELDS = ['first_name', 'family_name', 'student_zone', 'gender'];

    private const ZONE_ALIASES = [
        'east_gaza'  => 'East Gaza',  'east gaza'  => 'East Gaza',  'شرق غزة' => 'East Gaza',
        'west_gaza'  => 'West Gaza',  'west gaza'  => 'West Gaza',  'غرب غزة' => 'West Gaza',
        'north_gaza' => 'North Gaza', 'north gaza' => 'North Gaza', 'شمال غزة' => 'North Gaza',
        'south_gaza' => 'South Gaza', 'south gaza' => 'South Gaza', 'جنوب غزة' => 'South Gaza',
    ];

    private const GENDER_ALIASES = [
        'male'   => 'male',   'm' => 'male',   'ذكر'  => 'male',
        'female' => 'female', 'f' => 'female', 'أنثى' => 'female', 'انثى' => 'female',
    ];

    /**
     * @return array{
     *   imported: int,
     *   failed_rows: array<int, array{row_number:int, raw:array<int, mixed>, error:string}>,
     *   stored_path: string,
     *   errors_csv_path: ?string
     * }
     */
    public function import(string $filePath, ?string $originalName = null): array
    {
        $storedRelativePath = $this->archiveOriginal($filePath, $originalName);

        $spreadsheet = IOFactory::load($filePath);
        $rows        = $this->extractRows($spreadsheet);
        $headerRow   = $rows[0] ?? [];
        $columnMap   = $this->mapHeaders($headerRow);

        // Only the required fields must be present in the header. Optional
        // columns can be missing entirely.
        $missing = array_diff(self::REQUIRED_FIELDS, array_keys($columnMap));
        if ($missing) {
            throw new \RuntimeException('الأعمدة المطلوبة مفقودة: ' . implode('، ', $missing));
        }

        // BR-SS-3: parse + validate every row FIRST. Only if every row is clean
        // do we touch the DB. This is the all-or-nothing guarantee.
        $parsed      = [];
        $failedRows  = [];
        $rowIndex    = 1;
        foreach (array_slice($rows, 1) as $row) {
            $rowIndex++;
            if ($this->isBlankRow($row)) {
                continue;
            }
            try {
                $parsed[] = $this->parseRow($row, $columnMap);
            } catch (Throwable $e) {
                $failedRows[] = [
                    'row_number' => $rowIndex,
                    'raw'        => $row,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        $stats = [
            'imported'        => 0,
            'failed_rows'     => $failedRows,
            'stored_path'     => $storedRelativePath,
            'errors_csv_path' => null,
        ];

        if (! empty($failedRows)) {
            $stats['errors_csv_path'] = $this->writeErrorsCsv(
                $storedRelativePath, $headerRow, $failedRows
            );
            return $stats;
        }

        // All rows clean — replace the table atomically.
        DB::transaction(function () use ($parsed, &$stats) {
            DB::table('suggested_students')->delete();
            foreach (array_chunk($parsed, 500) as $batch) {
                $now = now();
                SuggestedStudent::insert(array_map(
                    fn($row) => $row + ['created_at' => $now, 'updated_at' => $now],
                    $batch
                ));
            }
            $stats['imported'] = count($parsed);
        });

        return $stats;
    }

    public function deleteAll(): int
    {
        return DB::table('suggested_students')->delete();
    }

    private function archiveOriginal(string $sourcePath, ?string $originalName): string
    {
        $disk    = Storage::disk(self::STORAGE_DISK);
        $datedir = now()->format('Y-m-d');
        $stamp   = now()->format('Ymd-His');
        $base    = $originalName ? pathinfo($originalName, PATHINFO_FILENAME) : 'import';
        $ext     = $originalName
            ? (pathinfo($originalName, PATHINFO_EXTENSION) ?: 'xlsx')
            : (pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'xlsx');

        $safeBase = Str::slug($base, '-', null) ?: 'import';
        $filename = "{$stamp}_{$safeBase}.{$ext}";
        $relative = self::STORAGE_DIR . "/{$datedir}/{$filename}";

        $disk->put($relative, file_get_contents($sourcePath));

        return $relative;
    }

    /**
     * @param array<int, mixed> $headerRow
     * @param array<int, array{row_number:int, raw:array<int, mixed>, error:string}> $failedRows
     */
    private function writeErrorsCsv(string $originalRelativePath, array $headerRow, array $failedRows): string
    {
        $errorsRelative = preg_replace('/\.[^.]+$/', '.errors.csv', $originalRelativePath);

        $headers = [];
        $maxCol  = 0;
        foreach ($headerRow as $i => $name) {
            $headers[$i] = trim((string) $name) !== '' ? trim((string) $name) : "col_{$i}";
            $maxCol = max($maxCol, $i);
        }
        foreach ($failedRows as $f) {
            foreach (array_keys($f['raw']) as $i) {
                $maxCol = max($maxCol, $i);
            }
        }
        for ($i = 0; $i <= $maxCol; $i++) {
            $headers[$i] = $headers[$i] ?? "col_{$i}";
        }
        ksort($headers);

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 Arabic correctly.
        fputcsv($fh, array_merge(['row_number'], array_values($headers), ['error']));

        foreach ($failedRows as $f) {
            $line = [$f['row_number']];
            for ($i = 0; $i <= $maxCol; $i++) {
                $line[] = $f['raw'][$i] ?? '';
            }
            $line[] = $f['error'];
            fputcsv($fh, $line);
        }

        rewind($fh);
        $contents = stream_get_contents($fh);
        fclose($fh);

        Storage::disk(self::STORAGE_DISK)->put($errorsRelative, $contents);

        return $errorsRelative;
    }

    /** @return array<int, array<int, mixed>> */
    private function extractRows(Spreadsheet $spreadsheet): array
    {
        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    /** @return array<string, int> canonical field name → column index */
    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $colIndex => $value) {
            $normalized = $this->normalizeHeader((string) $value);
            foreach (self::HEADER_ALIASES as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    if ($this->normalizeHeader($alias) === $normalized) {
                        $map[$canonical] = $colIndex;
                        continue 3;
                    }
                }
            }
        }
        return $map;
    }

    /** @return array<string, mixed> */
    private function parseRow(array $row, array $columnMap): array
    {
        $get = fn(string $key) => isset($columnMap[$key]) ? ($row[$columnMap[$key]] ?? null) : null;

        $firstName  = $this->requiredString($get('first_name'), 'الاسم الأول');
        $familyName = $this->requiredString($get('family_name'), 'اسم العائلة');
        $zone       = $this->resolveZone($get('student_zone'));
        $gender     = $this->resolveGender($get('gender'));
        $nationalId = $this->resolveNationalId($get('national_id'));

        return [
            'national_id'      => $nationalId,
            'first_name'       => $firstName,
            'second_name'      => $this->optionalString($get('second_name')),
            'third_name'       => $this->optionalString($get('third_name')),
            'family_name'      => $familyName,
            'student_zone'     => $zone,
            'gender'           => $gender,
            'is_recite_before' => $this->resolveBool($get('is_recite_before')),
        ];
    }

    private function requiredString(mixed $value, string $label): string
    {
        $s = $value === null ? '' : trim((string) $value);
        if ($s === '') {
            throw new \RuntimeException("{$label} مطلوب.");
        }
        if (mb_strlen($s) > 50) {
            throw new \RuntimeException("{$label} يجب ألا يتجاوز 50 حرفاً.");
        }
        return $s;
    }

    private function optionalString(mixed $value): ?string
    {
        $s = $value === null ? '' : trim((string) $value);
        if ($s === '') {
            return null;
        }
        if (mb_strlen($s) > 50) {
            throw new \RuntimeException('أحد الأسماء يتجاوز 50 حرفاً.');
        }
        return $s;
    }

    private function resolveZone(mixed $value): string
    {
        $s = $value === null ? '' : trim((string) $value);
        if ($s === '') {
            throw new \RuntimeException('المنطقة مطلوبة.');
        }
        if (in_array($s, self::ZONES, true)) {
            return $s;
        }
        $key = mb_strtolower($s);
        if (isset(self::ZONE_ALIASES[$key])) {
            return self::ZONE_ALIASES[$key];
        }
        throw new \RuntimeException("المنطقة \"{$value}\" غير مقبولة. القيم: East Gaza, West Gaza, North Gaza, South Gaza.");
    }

    private function resolveGender(mixed $value): string
    {
        $s = $value === null ? '' : trim((string) $value);
        if ($s === '') {
            throw new \RuntimeException('الجنس مطلوب.');
        }
        $key = mb_strtolower($s);
        if (isset(self::GENDER_ALIASES[$key])) {
            return self::GENDER_ALIASES[$key];
        }
        throw new \RuntimeException("الجنس \"{$value}\" غير مقبول. القيم: male, female.");
    }

    private function resolveNationalId(mixed $value): ?string
    {
        $s = $value === null ? '' : trim((string) $value);
        if ($s === '') {
            return null;
        }
        // Strip a trailing ".0" that Excel adds when a numeric cell holds a
        // 9-digit national id without explicit text formatting.
        if (preg_match('/^(\d+)\.0+$/', $s, $m)) {
            $s = $m[1];
        }
        if (! preg_match('/^\d{9}$/', $s)) {
            throw new \RuntimeException("رقم الهوية \"{$value}\" يجب أن يكون 9 أرقام بالضبط.");
        }
        return $s;
    }

    private function resolveBool(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        $s = mb_strtolower(trim((string) $value));
        if ($s === '' || in_array($s, ['0', 'false', 'no', 'لا', 'كلا'], true)) {
            return false;
        }
        if (in_array($s, ['1', 'true', 'yes', 'نعم'], true)) {
            return true;
        }
        throw new \RuntimeException("قيمة \"سبق التسميع\" غير مفهومة: \"{$value}\" (المقبول: true/false/1/0).");
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function normalizeHeader(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return mb_strtolower($text);
    }

    /** Builds an empty xlsx template with headers + one example row. */
    public function buildTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('suggested_students');

        $headers = [
            'national_id', 'first_name', 'second_name', 'third_name',
            'family_name', 'student_zone', 'gender', 'is_recite_before',
        ];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        // Example row to show the expected format.
        $example = ['123456789', 'محمد', 'أحمد', '', 'الفلسطيني', 'East Gaza', 'male', 'false'];
        foreach ($example as $i => $val) {
            $sheet->setCellValueByColumnAndRow($i + 1, 2, $val);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'suggested-students-template-') . '.xlsx';
        $writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);
        return $tempPath;
    }
}
