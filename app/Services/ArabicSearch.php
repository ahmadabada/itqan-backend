<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * Lenient Arabic-name search:
 *  - Normalizes letter variants (أ/إ/آ → ا, ة → ه, ى → ي, ؤ → و, ئ → ي)
 *  - Strips diacritics (tashkil) and tatweel
 *  - Splits query into words; each word can match any part of the full name
 *
 * Usage:
 *   ArabicSearch::applyTo($query, $search, ['first_name', 'second_name', 'family_name']);
 */
class ArabicSearch
{
    /** Letter variants → canonical form */
    private const REPLACEMENTS = [
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ٱ' => 'ا',
        'ى' => 'ي',
        'ة' => 'ه',
        'ؤ' => 'و',
        'ئ' => 'ي',
    ];

    /** Tashkil (diacritics) + tatweel — stripped entirely */
    private const STRIP_PATTERN = '/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u';

    /**
     * Normalize an Arabic string to a canonical form for comparison.
     */
    public static function normalize(?string $text): string
    {
        if ($text === null || $text === '') return '';

        $text = preg_replace(self::STRIP_PATTERN, '', $text);
        $text = strtr($text, self::REPLACEMENTS);
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Build a MySQL expression that normalizes a column at query time.
     * (Avoids needing a separate stored column. OK for <100k rows.)
     */
    private static function normalizeColumnExpr(string $column): string
    {
        $expr = "LOWER(`{$column}`)";
        foreach (self::REPLACEMENTS as $from => $to) {
            $expr = "REPLACE({$expr}, '{$from}', '{$to}')";
        }
        return $expr;
    }

    /**
     * Apply lenient name search to a query builder.
     *
     * Matches each word in the search against the concatenated, normalized name fields.
     * Works alongside other where clauses (it wraps itself in a where group).
     *
     * @param  array<string>  $nameFields  e.g. ['first_name', 'second_name', 'third_name', 'family_name']
     * @param  array<string>  $exactFields columns matched literally with LIKE (e.g. ['national_id'])
     */
    public static function applyTo(Builder $query, string $search, array $nameFields, array $exactFields = []): void
    {
        $normalized = self::normalize($search);
        if ($normalized === '') return;

        $words = array_filter(explode(' ', $normalized));
        if (empty($words)) return;

        // Build a normalized CONCAT_WS over the name fields
        $normalizedExprs = array_map(fn($f) => self::normalizeColumnExpr($f), $nameFields);
        $concat          = 'CONCAT_WS(\' \', ' . implode(', ', $normalizedExprs) . ')';

        $query->where(function (Builder $q) use ($concat, $words, $exactFields, $search) {
            // ── Name match: every word must appear somewhere in the concatenated name ──
            $q->where(function (Builder $sub) use ($concat, $words) {
                foreach ($words as $word) {
                    $sub->whereRaw("{$concat} LIKE ?", ['%' . $word . '%']);
                }
            });

            // ── Or exact-field match (e.g. national_id, no normalization) ──
            foreach ($exactFields as $field) {
                $q->orWhere($field, 'like', '%' . $search . '%');
            }
        });
    }
}
