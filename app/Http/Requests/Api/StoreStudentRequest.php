<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required when added from the API (Flutter app needs it for offline matching).
            'national_id'  => ['required', 'digits:9', 'unique:students,national_id'],
            'first_name'   => ['required', 'string', 'max:50'],
            'second_name'  => ['nullable', 'string', 'max:50'],
            'third_name'   => ['nullable', 'string', 'max:50'],
            'family_name'  => ['required', 'string', 'max:50'],
            'gender'       => ['required', 'in:male,female'],
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.digits' => 'رقم الهوية يجب أن يكون 9 أرقام.',
            'national_id.unique' => 'رقم الهوية مسجّل لطالب آخر.',
            'gender.required'    => 'الجنس مطلوب.',
        ];
    }
}
