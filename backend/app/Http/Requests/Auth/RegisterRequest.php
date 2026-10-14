<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required_without:phone', 'nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'gamer_tag' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:profiles,gamer_tag'],
            'university' => ['nullable', 'string', 'max:150'],
            'is_minor' => ['sometimes', 'boolean'],
            'locale' => ['sometimes', 'string', 'in:en,am'],
        ];
    }
}
