<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_hash' => ['nullable', 'string', 'max:128'],
            'is_webdriver' => ['nullable', 'boolean'],
            'is_emulator' => ['nullable', 'boolean'],
        ];
    }
}
