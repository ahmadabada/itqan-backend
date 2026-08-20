<?php

namespace App\Services;

use App\Models\Exam;

class ExamApprovalService
{
    // Keep exactly one approved exam per student within a single round.
    public function demoteOthersInRound(Exam $approvedExam): void
    {
        Exam::where('student_id', $approvedExam->student_id)
            ->where('exam_round_id', $approvedExam->exam_round_id)
            ->where('id', '!=', $approvedExam->id)
            ->where('status', 'approved')
            ->update([
                'status'      => 'excluded',
                'is_approved' => false,
            ]);
    }
}
