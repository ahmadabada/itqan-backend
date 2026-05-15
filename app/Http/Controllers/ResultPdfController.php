<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

// BR-QUERY-04: PDF available for all students (passing or not)
class ResultPdfController extends Controller
{
    public function __invoke(int $examId): Response
    {
        // Only serve if query is enabled (BR-QUERY-03)
        if (! SystemSetting::get('results_query_enabled', false)) {
            abort(403, 'الاستعلام عن النتائج غير متاح حالياً.');
        }

        $exam = Exam::where('id', $examId)
            ->where('is_approved', true)
            ->with(['student', 'questions'])
            ->firstOrFail();

        $academyName = SystemSetting::get('academy_name', 'أكاديمية الإتقان');

        $pdf = Pdf::loadView('pdf.exam-result', [
            'exam'        => $exam,
            'student'     => $exam->student,
            'academyName' => $academyName,
        ])->setPaper('a4');

        $filename = 'نتيجة-' . $exam->student->national_id . '.pdf';

        return $pdf->download($filename);
    }
}
