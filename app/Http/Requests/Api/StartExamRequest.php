<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'         => ['required', 'integer', 'exists:students,id'],
            'exam_type'          => ['required', 'in:full_quran,half_quran'],
            'reexam_permit_code' => ['nullable', 'string'],
        ];
    }
}
