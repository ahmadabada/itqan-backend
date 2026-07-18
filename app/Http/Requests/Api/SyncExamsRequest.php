<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

// The device uploads exams against students that already exist on the server —
// it carries a downloaded roster and sends student_id, never student details.
// A student may sit many exams, so nothing here guards against repeats.
class SyncExamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exams'                                   => ['required', 'array', 'min:1'],
            'exams.*.client_request_id'               => ['required', 'string', 'size:36'],
            'exams.*.student_id'                      => ['required', 'integer', 'exists:students,id'],

            // Scope of the exam — documentary, but the examiner must state it.
            // Fractional parts are allowed (e.g. 5.5), up to 2 decimals, capped at 30.
            'exams.*.parts_count'                     => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:30'],
            'exams.*.new_memorization_parts'          => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:exams.*.parts_count'],

            'exams.*.rulings_score'                   => ['required', 'numeric', 'min:0', 'max:10'],
            'exams.*.total_score'                     => ['required', 'numeric', 'min:0', 'max:100'],
            'exams.*.device_uuid'                     => ['required', 'string', 'max:64'],
            'exams.*.started_at'                      => ['required', 'date'],
            'exams.*.completed_at'                    => ['required', 'date'],

            'exams.*.questions'                       => ['required', 'array', 'size:3'],
            'exams.*.questions.*.question_number'     => ['required', 'integer', 'between:1,3'],
            'exams.*.questions.*.errors_count'        => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.warnings_count'      => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.continuations_count' => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.final_score'         => ['required', 'numeric', 'min:0', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'exams.*.student_id.exists' => 'الطالب غير موجود على الخادم — يجب أن يضيفه الأدمن أولاً ثم يُحدَّث الجهاز.',
            'exams.*.new_memorization_parts.lte' => 'أجزاء الحفظ الجديد لا يمكن أن تتجاوز عدد الأجزاء المختبر فيها.',
        ];
    }
}
