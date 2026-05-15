<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @font-face {
            font-family: 'Tajawal';
            src: local('Tajawal');
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            color: #18181B;
            background: #fff;
            padding: 40px;
        }
        .header { text-align: center; border-bottom: 2px solid #024A7D; padding-bottom: 16px; margin-bottom: 24px; }
        .academy-name { font-size: 22px; font-weight: bold; color: #024A7D; margin-bottom: 4px; }
        .doc-title { font-size: 16px; color: #71717A; }
        .result-box {
            border: 2px solid {{ $exam->is_passed ? '#16A34A' : '#DC2626' }};
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
            background: {{ $exam->is_passed ? '#F0FDF4' : '#FEF2F2' }};
        }
        .score { font-size: 56px; font-weight: bold; color: {{ $exam->is_passed ? '#15803D' : '#B91C1C' }}; line-height: 1; }
        .out-of { font-size: 14px; color: #71717A; margin-top: 4px; }
        .status-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 6px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            background: {{ $exam->is_passed ? '#DCFCE7' : '#FEE2E2' }};
            color: {{ $exam->is_passed ? '#15803D' : '#B91C1C' }};
        }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 13px; }
        .info-table td { padding: 10px 12px; border-bottom: 1px solid #E4E4E7; }
        .info-table td:first-child { color: #71717A; width: 40%; }
        .info-table td:last-child { font-weight: 500; }
        .footer { text-align: center; margin-top: 40px; font-size: 11px; color: #A1A1AA; border-top: 1px solid #E4E4E7; padding-top: 12px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="academy-name">{{ $academyName }}</div>
        <div class="doc-title">وثيقة نتيجة اختبار القرآن الكريم</div>
    </div>

    <div class="result-box">
        <div class="score">{{ number_format($exam->total_score, 1) }}</div>
        <div class="out-of">من 100 درجة</div>
        <div class="status-badge">
            {{ $exam->is_passed ? '✓ مجاز' : '✗ غير مجاز' }}
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td>الاسم الكامل</td>
            <td>{{ $student->fullName() }}</td>
        </tr>
        <tr>
            <td>رقم الهوية</td>
            <td>{{ $student->national_id }}</td>
        </tr>
        <tr>
            <td>نوع الاختبار</td>
            <td>{{ $exam->exam_type->value === 'full_quran' ? 'القرآن الكريم كاملاً' : 'نصف القرآن الكريم' }}</td>
        </tr>
        <tr>
            <td>تاريخ الاختبار</td>
            <td>{{ $exam->completed_at?->format('Y/m/d') ?? '—' }}</td>
        </tr>
        <tr>
            <td>درجة الأحكام</td>
            <td>{{ $exam->rulings_score }} / 10</td>
        </tr>
    </table>

    <div class="footer">
        {{ $academyName }} — {{ now()->format('Y/m/d') }}
    </div>

</body>
</html>
