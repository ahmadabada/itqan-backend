<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'national_id' => ['required', 'digits:9'],
            'password'    => ['required', 'string'],
            'device_uuid' => ['required', 'string', 'max:64'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.digits' => 'رقم الهوية يجب أن يكون 9 أرقام.',
        ];
    }
}
