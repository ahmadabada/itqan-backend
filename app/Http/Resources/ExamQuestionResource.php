<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'question_number'     => $this->question_number,
            'errors_count'        => $this->errors_count,
            'warnings_count'      => $this->warnings_count,
            'continuations_count' => $this->continuations_count,
            'final_score'         => (float) $this->final_score,
        ];
    }
}
