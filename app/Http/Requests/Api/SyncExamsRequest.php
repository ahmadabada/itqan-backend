<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

// Record-per-exam payload: every offline exam carries its own student. The server
// never looks up an existing student by national_id — duplicates are intentional and
// the admin merges them after the exam period via the merge UI.
class SyncExamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exams'                                       => ['required', 'array', 'min:1'],
            'exams.*.client_request_id'                   => ['required', 'string', 'size:36'],

            // The student that took this exam — one-to-one per offline submission.
            'exams.*.student'                             => ['required', 'array'],
            'exams.*.student.client_request_id'           => ['required', 'string', 'size:36'],
            'exams.*.student.national_id'                 => ['required', 'string', 'max:20'],
            'exams.*.student.first_name'                  => ['required', 'string', 'max:50'],
            'exams.*.student.second_name'                 => ['nullable', 'string', 'max:50'],
            'exams.*.student.third_name'                  => ['nullable', 'string', 'max:50'],
            'exams.*.student.family_name'                 => ['required', 'string', 'max:50'],
            'exams.*.student.gender'                      => ['required', 'in:male,female'],

            'exams.*.exam_type'                           => ['required', 'in:full_quran,half_quran'],
            'exams.*.selected_groups'                     => ['nullable', 'array'],
            'exams.*.selected_groups.*'                   => ['integer', 'between:1,6'],
            'exams.*.rulings_score'                       => ['required', 'numeric', 'min:0', 'max:10'],
            'exams.*.total_score'                         => ['required', 'numeric', 'min:0', 'max:100'],
            'exams.*.device_uuid'                         => ['required', 'string', 'max:64'],
            'exams.*.started_at'                          => ['required', 'date'],
            'exams.*.completed_at'                        => ['required', 'date'],

            'exams.*.questions'                           => ['required', 'array', 'size:3'],
            'exams.*.questions.*.question_number'         => ['required', 'integer', 'between:1,3'],
            'exams.*.questions.*.recitation_question_id'  => ['nullable', 'integer', 'exists:recitation_questions,id'],
            'exams.*.questions.*.errors_count'            => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.warnings_count'          => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.continuations_count'     => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.final_score'             => ['required', 'numeric', 'min:0', 'max:30'],
        ];
    }
}
