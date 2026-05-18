<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_uuid'  => ['required', 'string', 'max:64'],
            'fcm_token'    => ['nullable', 'string'],
            'fcm_platform' => ['nullable', 'string', 'in:android,ios'],
        ];
    }
}
