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
            'national_id'  => ['required', 'string'],
            'first_name'   => ['required', 'string', 'max:50'],
            'second_name'  => ['nullable', 'string', 'max:50'],
            'third_name'   => ['nullable', 'string', 'max:50'],
            'family_name'  => ['required', 'string', 'max:50'],
        ];
    }
}
