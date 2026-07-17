<?php

namespace App\Services;

use App\Enums\Halaqah;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

/**
 * Imports the student roster from xlsx/xls/csv into `students`.
 *
 * Replaced the suggested-students import on 2026-07-17. Students are now the
 * single source of truth: the examiner picks from this roster and can no longer
 * create a student in the field, so what lands here is what can be examined.
 *
 * Semantics:
 *   • UPSERT on national_id — never a truncate. Existing students carry exams,
 *     and re-importing must not orphan or duplicate them.
 *   • Validation is all-or-nothing: if any row fails, NOTHING is written; the
 *     admin gets a row-by-row error report instead of a half-imported roster.
 *   • Students already in the DB but absent from the file are LEFT ALONE. The
 *     file adds and corrects; it does not define the whole roster. Removing a
 *     student is a deliberate act on the students screen.
 *   • Soft-deleted students are also left alone — a deleted student reappearing
 *     in a file does not silently come back. Restore them explicitly.
 *
 * Every import (success or failure) is archived under
 * `storage/app/private/imports/students/{YYYY-MM-DD}/`. If validation fails, an
 * `.errors.csv` companion is written next to the original so the admin can fix
 * and re-upload.
 *
 * Expected columns (header row required, order flexible by header text):
 *   national_id | first_name | second_name | third_name | family_name |
 *   halaqah | gender
 */
class StudentImportService
{
    private const STORAGE_DISK = 'local';
    private const STORAGE_DIR  = 'imports/students';

    private const HEADER_ALIASES = [
        'national_id' => ['national_id', 'رقم الهوية', 'الهوية'],
        'first_name'  => ['first_name', 'الاسم الأول', 'الاسم'],
        'second_name' => ['second_name', 'اسم الأب', 'الاسم الثاني'],
        'third_name'  => ['third_name', 'اسم الجد', 'الاسم الثالث'],
        'family_name' => ['family_name', 'اسم العائلة', 'العائلة'],
        'halaqah'     => ['halaqah', 'الحلقة', 'اسم الحلقة'],
        'gender'      => ['gender', 'الجنس'],
    ];

    private const REQUIRED_FIELDS = ['national_id', 'first_name', 'family_name', 'halaqah', 'gender'];

    private const GENDER_ALIASES = [
        'male'   => 'male',   'm' => 'male',   'ذكر'  => 'male',
        'female' => 'female', 'f' => 'female', 'أنثى' => 'female', 'انثى' => 'female',
    ];

    /**
     * @return array{
     *   inserted: int,
     *   updated: int,
     *   failed_rows: array<int, array{row_number:int, raw:array<int, mixed>, error:string}>,
     *   stored_path: string,
     *   errors_csv_path: ?string
     * }
     */
    public function import(string $filePath, ?int $importedByUserId = null, ?string $originalName = null): array
    {
        $storedRelativePath = $this->archiveOriginal($filePath, $originalName);

        $spreadsheet = IOFactory::load($filePath);
        $rows        = $this->extractRows($spreadsheet);
        $headerRow   = $rows[0] ?? [];
        $columnMap   = $this->mapHeaders($headerRow);

        $missing = array_diff(self::REQUIRED_FIELDS, array_keys($columnMap));
        if ($missing) {
            throw new \RuntimeException('الأعمدة المطلوبة مفقودة: ' . implode('، ', $missing));
        }

        // Parse + validate every row FIRST. Only if every row is clean do we
        // touch the DB. This is the all-or-nothing guarantee.
        $parsed     = [];
        $seenIds    = [];
        $failedRows = [];
        $rowIndex   = 1;
        foreach (array_slice($rows, 1) as $row) {
            $rowIndex++;
            if ($this->isBlankRow($row)) {
                continue;
            }
            try {
                $record = $this->parseRow($row, $columnMap);

                // national_id is the upsert key, so a file that repeats one would
                // silently collapse two people into whichever row came last.
                if (isset($seenIds[$record['national_id']])) {
                    throw new \RuntimeException(
                        "رقم الهوية \"{$record['national_id']}\" مكرر في الملف (تكرر في الصف {$seenIds[$record['national_id']]})."
                    );
                }
                $seenIds[$record['national_id']] = $rowIndex;

                $parsed[] = $record;
            } catch (Throwable $e) {
                $failedRows[] = [
                    'row_number' => $rowIndex,
                    'raw'        => $row,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        $stats = [
            'inserted'        => 0,
            'updated'         => 0,
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

        DB::transaction(function () use ($parsed, $importedByUserId, &$stats) {
            $incoming = array_column($parsed, 'national_id');

            // Counted before the write, since MySQL's affected-rows can't tell an
            // insert from an update reliably. withTrashed(): a soft-deleted row
            // still occupies the unique national_id, so it counts as existing.
            $existing = Student::withTrashed()
                ->whereIn('national_id', $incoming)
                ->pluck('national_id')
                ->all();

            $now = now();
            foreach (array_chunk($parsed, 500) as $batch) {
                Student::upsert(
                    array_map(fn($row) => $row + [
                        'created_by_user_id' => $importedByUserId,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ], $batch),
                    ['national_id'],
                    // Only these are refreshed on conflict — created_at,
                    // created_by_user_id and deleted_at stay as they were.
                    ['first_name', 'second_name', 'third_name', 'family_name', 'gender', 'halaqah', 'updated_at'],
                );
            }

            $stats['updated']  = count($existing);
            $stats['inserted'] = count($parsed) - count($existing);
        });

        return $stats;
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

        return [
            'national_id' => $this->resolveNationalId($get('national_id')),
            'first_name'  => $this->requiredString($get('first_name'), 'الاسم الأول'),
            'second_name' => $this->optionalString($get('second_name')),
            'third_name'  => $this->optionalString($get('third_name')),
            'family_name' => $this->requiredString($get('family_name'), 'اسم العائلة'),
            'gender'      => $this->resolveGender($get('gender')),
            'halaqah'     => $this->resolveHalaqah($get('halaqah')),
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

    private function resolveHalaqah(mixed $value): string
    {
        $s = $value === null ? '' : trim((string) $value);
        if ($s === '') {
            throw new \RuntimeException('الحلقة مطلوبة.');
        }

        // Accept the enum value ("fadi_qazaar") or the Arabic label ("فادي قزاعر"),
        // which is what admins actually type.
        foreach (Halaqah::cases() as $case) {
            if ($case->value === mb_strtolower($s) || $this->normalizeArabic($case->label()) === $this->normalizeArabic($s)) {
                return $case->value;
            }
        }

        $accepted = implode('، ', array_map(fn($c) => $c->label(), Halaqah::cases()));
        throw new \RuntimeException("الحلقة \"{$value}\" غير مقبولة. القيم: {$accepted}.");
    }

    // Admins type halaqah names by hand, so tolerate the spelling drift that
    // carries no meaning: alef forms, taa marbuta, tatweel, and spacing.
    private function normalizeArabic(string $text): string
    {
        $text = preg_replace('/[\x{0640}\x{064B}-\x{0652}]/u', '', $text); // tatweel + harakat
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه']);
        $text = preg_replace('/\s+/u', ' ', $text);
        return mb_strtolower(trim($text));
    }

    private function resolveNationalId(mixed $value): string
    {
        $s = $value === null ? '' : trim((string) $value);
        if ($s === '') {
            throw new \RuntimeException('رقم الهوية مطلوب.');
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
        $sheet->setTitle('students');

        $headers = [
            'national_id', 'first_name', 'second_name', 'third_name',
            'family_name', 'halaqah', 'gender',
        ];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }

        $example = ['123456789', 'محمد', 'أحمد', '', 'الفلسطيني', Halaqah::FadiQazaar->label(), 'male'];
        foreach ($example as $i => $val) {
            $sheet->setCellValue([$i + 1, 2], $val);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'students-template-') . '.xlsx';
        $writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);
        return $tempPath;
    }
}
