<?php

namespace App\Services;

use App\Models\RecitationQuestion;
use App\Support\Surah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

/**
 * Imports the recitation question bank from xlsx/xls/csv. Surah columns accept
 * either an Arabic name (resolved via App\Support\Surah) or a 1–114 number.
 *
 * Every import — whether it succeeds or fails — is archived under
 * `storage/app/private/imports/recitation-questions/{YYYY-MM-DD}/`. When rows
 * fail, an `.errors.csv` companion file is written next to the original so the
 * admin can fix and re-upload only the bad rows.
 *
 * Expected columns (header row required, order flexible by header text):
 *   question_number | group_number |
 *   start_surah | start_ayah | start_page |
 *   end_surah   | end_ayah   | end_page
 *
 * Header aliases (Arabic accepted): see HEADER_ALIASES.
 */
class RecitationQuestionImportService
{
    private const STORAGE_DISK = 'local';
    private const STORAGE_DIR  = 'imports/recitation-questions';

    private const HEADER_ALIASES = [
        'question_number' => ['question_number', 'رقم السؤال', 'السؤال', '#'],
        'group_number'    => ['group_number', 'المجموعة', 'رقم المجموعة', 'group'],
        'start_surah'     => ['start_surah', 'سورة البداية', 'البداية - سورة', 'سورة_البداية'],
        'start_ayah'      => ['start_ayah', 'آية البداية', 'البداية - آية'],
        'start_page'      => ['start_page', 'صفحة البداية', 'البداية - صفحة'],
        'end_surah'       => ['end_surah', 'سورة النهاية', 'النهاية - سورة', 'سورة_النهاية'],
        'end_ayah'        => ['end_ayah', 'آية النهاية', 'النهاية - آية'],
        'end_page'        => ['end_page', 'صفحة النهاية', 'النهاية - صفحة'],
    ];

    /**
     * @return array{
     *   imported: int,
     *   updated: int,
     *   skipped: int,
     *   failed_rows: array<int, array{row_number:int, raw:array<int, mixed>, error:string}>,
     *   stored_path: string,
     *   errors_csv_path: ?string
     * }
     */
    public function import(string $filePath, ?string $originalName = null, string $mode = 'replace'): array
    {
        // 1. Archive the original file before doing anything. This runs even if
        //    parsing later throws — we always want a record of what was uploaded.
        $storedRelativePath = $this->archiveOriginal($filePath, $originalName);

        $spreadsheet = IOFactory::load($filePath);
        $rows        = $this->extractRows($spreadsheet);
        $headerRow   = $rows[0] ?? [];
        $columnMap   = $this->mapHeaders($headerRow);

        $required = array_keys(self::HEADER_ALIASES);
        $missing  = array_diff($required, array_keys($columnMap));
        if ($missing) {
            throw new \RuntimeException('الأعمدة المطلوبة مفقودة: ' . implode('، ', $missing));
        }

        $stats = [
            'imported'        => 0,
            'updated'         => 0,
            'skipped'         => 0,
            'failed_rows'     => [],
            'stored_path'     => $storedRelativePath,
            'errors_csv_path' => null,
        ];

        DB::transaction(function () use ($rows, $columnMap, $mode, &$stats) {
            if ($mode === 'replace') {
                DB::table('recitation_questions')->delete();
            }

            $rowIndex = 1; // header is row 0 in our 0-based array; sheet rows start at 2 visually.
            foreach (array_slice($rows, 1) as $row) {
                $rowIndex++;
                if ($this->isBlankRow($row)) {
                    continue;
                }

                try {
                    $data = $this->parseRow($row, $columnMap);
                } catch (Throwable $e) {
                    $stats['failed_rows'][] = [
                        'row_number' => $rowIndex,
                        'raw'        => $row,
                        'error'      => $e->getMessage(),
                    ];
                    $stats['skipped']++;
                    continue;
                }

                $existing = $mode === 'upsert'
                    ? RecitationQuestion::where('group_number', $data['group_number'])
                        ->where('question_number', $data['question_number'])
                        ->first()
                    : null;

                if ($existing) {
                    $existing->update($data);
                    $stats['updated']++;
                } else {
                    RecitationQuestion::create($data);
                    $stats['imported']++;
                }
            }
        });

        // 2. Write the companion errors CSV next to the archived original.
        if (! empty($stats['failed_rows'])) {
            $stats['errors_csv_path'] = $this->writeErrorsCsv(
                $storedRelativePath,
                $headerRow,
                $stats['failed_rows']
            );
        }

        return $stats;
    }

    /**
     * Copy the uploaded file into permanent storage so it can be re-downloaded
     * for audit or re-imported later. Returns the relative path on disk.
     */
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
     * Write a CSV containing one row per failed input row, preserving the
     * original cell values and appending an `error` column with the reason.
     * Uses UTF-8 with BOM so Excel opens Arabic correctly.
     *
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
        // UTF-8 BOM so Excel detects encoding.
        fwrite($fh, "\xEF\xBB\xBF");
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
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray(null, true, true, false);
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

    /** @return array<string, int|bool> validated row ready for insert. */
    private function parseRow(array $row, array $columnMap): array
    {
        $get = fn(string $key) => $row[$columnMap[$key]] ?? null;

        $questionNumber = $this->intOrFail($get('question_number'), 'رقم السؤال');
        $groupNumber    = $this->intOrFail($get('group_number'), 'رقم المجموعة');

        if ($groupNumber < 1 || $groupNumber > 6) {
            throw new \RuntimeException("رقم المجموعة يجب أن يكون بين 1 و 6 (المستلم: {$groupNumber}).");
        }

        return [
            'question_number' => $questionNumber,
            'group_number'    => $groupNumber,
            'start_surah'     => $this->resolveSurah($get('start_surah'), 'سورة البداية'),
            'start_ayah'      => $this->intOrFail($get('start_ayah'), 'آية البداية'),
            'start_page'      => $this->intOrFail($get('start_page'), 'صفحة البداية'),
            'end_surah'       => $this->resolveSurah($get('end_surah'), 'سورة النهاية'),
            'end_ayah'        => $this->intOrFail($get('end_ayah'), 'آية النهاية'),
            'end_page'        => $this->intOrFail($get('end_page'), 'صفحة النهاية'),
            'is_active'       => true,
        ];
    }

    private function resolveSurah(mixed $value, string $fieldLabel): int
    {
        if ($value === null || $value === '') {
            throw new \RuntimeException("{$fieldLabel} مطلوب.");
        }

        // Numeric input: accept directly as surah number.
        if (is_numeric($value)) {
            $num = (int) $value;
            if ($num < 1 || $num > 114) {
                throw new \RuntimeException("{$fieldLabel}: الرقم {$num} خارج النطاق 1–114.");
            }
            return $num;
        }

        $num = Surah::numberFor((string) $value);
        if ($num === null) {
            throw new \RuntimeException("{$fieldLabel}: لم يتم التعرف على السورة \"{$value}\".");
        }
        return $num;
    }

    private function intOrFail(mixed $value, string $fieldLabel): int
    {
        if ($value === null || $value === '') {
            throw new \RuntimeException("{$fieldLabel} مطلوب.");
        }
        if (! is_numeric($value)) {
            throw new \RuntimeException("{$fieldLabel}: يجب أن يكون رقماً (المستلم: \"{$value}\").");
        }
        return (int) $value;
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
}
