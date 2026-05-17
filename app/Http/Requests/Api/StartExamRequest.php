<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

            // BR-EXAM-11: half_quran requires exactly 3 distinct group selections (1..6).
            'selected_groups'    => [
                Rule::requiredIf(fn() => $this->input('exam_type') === 'half_quran'),
                'array',
                'size:3',
            ],
            'selected_groups.*'  => ['integer', 'between:1,6', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'selected_groups.required' => 'يجب اختيار 3 مجموعات لاختبار نصف القرآن.',
            'selected_groups.size'     => 'يجب اختيار 3 مجموعات بالضبط.',
            'selected_groups.*.distinct' => 'لا يمكن تكرار نفس المجموعة.',
            'selected_groups.*.between'  => 'رقم المجموعة يجب أن يكون بين 1 و 6.',
        ];
    }
}
