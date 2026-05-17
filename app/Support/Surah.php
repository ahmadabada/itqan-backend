<?php

namespace App\Support;

// Quran surahs 1–114. `numberFor()` accepts common Arabic name variants
// (with/without ال, alternate spellings, "آل عمران"/"ال عمران") so the
// Excel import is tolerant of editorial differences.
final class Surah
{
    private const NAMES = [
        1   => 'الفاتحة',
        2   => 'البقرة',
        3   => 'آل عمران',
        4   => 'النساء',
        5   => 'المائدة',
        6   => 'الأنعام',
        7   => 'الأعراف',
        8   => 'الأنفال',
        9   => 'التوبة',
        10  => 'يونس',
        11  => 'هود',
        12  => 'يوسف',
        13  => 'الرعد',
        14  => 'إبراهيم',
        15  => 'الحجر',
        16  => 'النحل',
        17  => 'الإسراء',
        18  => 'الكهف',
        19  => 'مريم',
        20  => 'طه',
        21  => 'الأنبياء',
        22  => 'الحج',
        23  => 'المؤمنون',
        24  => 'النور',
        25  => 'الفرقان',
        26  => 'الشعراء',
        27  => 'النمل',
        28  => 'القصص',
        29  => 'العنكبوت',
        30  => 'الروم',
        31  => 'لقمان',
        32  => 'السجدة',
        33  => 'الأحزاب',
        34  => 'سبأ',
        35  => 'فاطر',
        36  => 'يس',
        37  => 'الصافات',
        38  => 'ص',
        39  => 'الزمر',
        40  => 'غافر',
        41  => 'فصلت',
        42  => 'الشورى',
        43  => 'الزخرف',
        44  => 'الدخان',
        45  => 'الجاثية',
        46  => 'الأحقاف',
        47  => 'محمد',
        48  => 'الفتح',
        49  => 'الحجرات',
        50  => 'ق',
        51  => 'الذاريات',
        52  => 'الطور',
        53  => 'النجم',
        54  => 'القمر',
        55  => 'الرحمن',
        56  => 'الواقعة',
        57  => 'الحديد',
        58  => 'المجادلة',
        59  => 'الحشر',
        60  => 'الممتحنة',
        61  => 'الصف',
        62  => 'الجمعة',
        63  => 'المنافقون',
        64  => 'التغابن',
        65  => 'الطلاق',
        66  => 'التحريم',
        67  => 'الملك',
        68  => 'القلم',
        69  => 'الحاقة',
        70  => 'المعارج',
        71  => 'نوح',
        72  => 'الجن',
        73  => 'المزمل',
        74  => 'المدثر',
        75  => 'القيامة',
        76  => 'الإنسان',
        77  => 'المرسلات',
        78  => 'النبأ',
        79  => 'النازعات',
        80  => 'عبس',
        81  => 'التكوير',
        82  => 'الانفطار',
        83  => 'المطففين',
        84  => 'الانشقاق',
        85  => 'البروج',
        86  => 'الطارق',
        87  => 'الأعلى',
        88  => 'الغاشية',
        89  => 'الفجر',
        90  => 'البلد',
        91  => 'الشمس',
        92  => 'الليل',
        93  => 'الضحى',
        94  => 'الشرح',
        95  => 'التين',
        96  => 'العلق',
        97  => 'القدر',
        98  => 'البينة',
        99  => 'الزلزلة',
        100 => 'العاديات',
        101 => 'القارعة',
        102 => 'التكاثر',
        103 => 'العصر',
        104 => 'الهمزة',
        105 => 'الفيل',
        106 => 'قريش',
        107 => 'الماعون',
        108 => 'الكوثر',
        109 => 'الكافرون',
        110 => 'النصر',
        111 => 'المسد',
        112 => 'الإخلاص',
        113 => 'الفلق',
        114 => 'الناس',
    ];

    // Spelling variants that should resolve to the canonical name above.
    private const ALIASES = [
        'ال عمران'   => 'آل عمران',
        'طاها'       => 'طه',
        'يٰسٓ'        => 'يس',
        'ياسين'      => 'يس',
        'الإنشراح'   => 'الشرح',
        'الانشراح'   => 'الشرح',
        'الاسراء'    => 'الإسراء',
        'بني إسرائيل'=> 'الإسراء',
        'سبا'        => 'سبأ',
        'النبا'      => 'النبأ',
        'فاتحة الكتاب'=> 'الفاتحة',
        'محمّد'      => 'محمد',
        'حم السجدة'  => 'فصلت',
        'الدهر'      => 'الإنسان',
        'هل أتى'     => 'الإنسان',
        'المسبحات'   => 'الصف',
        'تبارك'      => 'الملك',
        'المعوذتين'  => 'الفلق',
    ];

    public static function nameFor(int $number): ?string
    {
        return self::NAMES[$number] ?? null;
    }

    /**
     * Resolve a (possibly messy) Arabic surah name to its 1–114 number.
     * Returns null when no match is found.
     */
    public static function numberFor(string $name): ?int
    {
        $normalized = self::normalize($name);

        // 1. Direct match against canonical names.
        foreach (self::NAMES as $num => $canonical) {
            if (self::normalize($canonical) === $normalized) {
                return $num;
            }
        }

        // 2. Aliases.
        if (isset(self::ALIASES[$name]) || isset(self::ALIASES[$normalized])) {
            $canonical = self::ALIASES[$name] ?? self::ALIASES[$normalized];
            return array_search($canonical, self::NAMES, true) ?: null;
        }

        // 3. Strip leading "سورة "/"سوره " prefix and retry.
        // Note: the normalized form already mapped ة → ه, so we match either form.
        $stripped = preg_replace('/^سوره\s+/u', '', $normalized);
        if ($stripped !== $normalized) {
            return self::numberFor($stripped);
        }

        return null;
    }

    public static function all(): array
    {
        return self::NAMES;
    }

    // Why: Arabic input often varies in diacritics, hamza forms, and ta marbuta.
    // Normalize before comparing so "البقرة"/"البقره"/"البَقَرَة" all match.
    private static function normalize(string $text): string
    {
        $text = trim($text);
        // Remove tatweel and tashkeel.
        $text = preg_replace('/[\x{0617}-\x{061A}\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $text);
        // Unify alef variants.
        $text = preg_replace('/[\x{0622}\x{0623}\x{0625}\x{0671}]/u', 'ا', $text);
        // Unify ya / alef maksura.
        $text = str_replace(['ى', 'ئ'], ['ي', 'ي'], $text);
        // Unify ta marbuta to ha.
        $text = str_replace('ة', 'ه', $text);
        // Collapse whitespace.
        $text = preg_replace('/\s+/u', ' ', $text);
        return $text;
    }
}
