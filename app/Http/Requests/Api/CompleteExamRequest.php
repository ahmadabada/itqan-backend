<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CompleteExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questions'                           => ['required', 'array', 'size:3'],
            'questions.*.question_number'         => ['required', 'integer', 'between:1,3'],
            'questions.*.errors_count'            => ['required', 'integer', 'min:0'],
            'questions.*.warnings_count'          => ['required', 'integer', 'min:0'],
            'questions.*.continuations_count'     => ['required', 'integer', 'min:0'],
            'questions.*.final_score'             => ['required', 'numeric', 'min:0', 'max:30'],
            'rulings_score'                       => ['required', 'numeric', 'min:0', 'max:10'],
        ];
    }
}
