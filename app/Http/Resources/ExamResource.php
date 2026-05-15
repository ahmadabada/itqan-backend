<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'student'        => $this->whenLoaded('student', fn() => new StudentResource($this->student)),
            'student_id'     => $this->student_id,
            'examiner_id'    => $this->examiner_id,
            'examiner'       => $this->whenLoaded('examiner', fn() => new UserResource($this->examiner)),
            'exam_type'      => $this->exam_type->value,
            'attempt_number' => $this->attempt_number,
            'rulings_score'  => $this->rulings_score ? (float) $this->rulings_score : null,
            'total_score'    => $this->total_score ? (float) $this->total_score : null,
            'is_passed'      => $this->is_passed,
            'is_approved'    => $this->is_approved,
            'status'         => $this->status->value,
            'questions'      => ExamQuestionResource::collection($this->whenLoaded('questions')),
            'started_at'     => $this->started_at?->toISOString(),
            'completed_at'   => $this->completed_at?->toISOString(),
        ];
    }
}
