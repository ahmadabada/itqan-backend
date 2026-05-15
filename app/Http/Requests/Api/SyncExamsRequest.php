<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

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
            'exams.*.local_id'                        => ['required', 'string'],
            'exams.*.student_id'                      => ['nullable', 'integer'],
            'exams.*.student_national_id'             => ['nullable', 'string'],
            'exams.*.is_manually_added_student'       => ['required', 'boolean'],
            'exams.*.exam_type'                       => ['required', 'in:full_quran,half_quran'],
            'exams.*.rulings_score'                   => ['required', 'numeric', 'min:0', 'max:10'],
            'exams.*.total_score'                     => ['required', 'numeric', 'min:0', 'max:100'],
            'exams.*.is_passed'                       => ['required', 'boolean'],
            'exams.*.started_at'                      => ['required', 'date'],
            'exams.*.completed_at'                    => ['required', 'date'],
            'exams.*.device_uuid'                     => ['required', 'string'],
            'exams.*.reexam_permit_code'              => ['nullable', 'string'],
            'exams.*.questions'                       => ['required', 'array', 'size:3'],
            'exams.*.questions.*.question_number'     => ['required', 'integer', 'between:1,3'],
            'exams.*.questions.*.errors_count'        => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.warnings_count'      => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.continuations_count' => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.final_score'         => ['required', 'numeric', 'min:0', 'max:30'],
            'exams.*.manual_student_data'             => ['nullable', 'array'],
            'exams.*.manual_student_data.national_id' => ['nullable', 'string'],
            'exams.*.manual_student_data.first_name'  => ['nullable', 'string'],
            'exams.*.manual_student_data.family_name' => ['nullable', 'string'],
        ];
    }

    // BR-SYNC-04: cross-field check — manual students must supply national_id; non-manual must supply student_id
    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->exams ?? [] as $i => $exam) {
                    if (($exam['is_manually_added_student'] ?? false) && empty($exam['manual_student_data']['national_id'])) {
                        $validator->errors()->add(
                            "exams.$i.manual_student_data.national_id",
                            'رقم الهوية مطلوب للطالب اليدوي.'
                        );
                    }

                    if (! ($exam['is_manually_added_student'] ?? false) && empty($exam['student_id'])) {
                        $validator->errors()->add("exams.$i.student_id", 'رقم الطالب مطلوب.');
                    }
                }
            },
        ];
    }
}
