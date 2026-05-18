<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RecitationQuestion;
use Illuminate\Http\JsonResponse;

class RecitationQuestionController extends Controller
{
    // GET /api/v1/recitation-questions
    // The mobile app pulls the question bank once on first launch and caches it
    // locally; examiners need to see the actual ayah range during the exam.
    public function index(): JsonResponse
    {
        $questions = RecitationQuestion::where('is_active', true)
            ->orderBy('group_number')
            ->orderBy('question_number')
            ->get([
                'id', 'question_number', 'group_number',
                'start_surah', 'start_ayah', 'start_page',
                'end_surah', 'end_ayah', 'end_page',
            ]);

        return response()->json([
            'questions' => $questions,
            'count'     => $questions->count(),
        ]);
    }
}
