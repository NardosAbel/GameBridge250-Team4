<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
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
        $profileId = $this->user()?->profile?->id;

        return [
            'gamer_tag' => [
                'sometimes', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('profiles', 'gamer_tag')->ignore($profileId),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar_url' => ['nullable', 'string', 'max:2048'],
            'cover_url' => ['nullable', 'string', 'max:2048'],
            'university' => ['nullable', 'string', 'max:150'],
            'school_id_number' => ['nullable', 'string', 'max:50'],
            'platform_handles' => ['nullable', 'array'],
            'preferred_games' => ['nullable', 'array'],
            'is_public' => ['sometimes', 'boolean'],
            'locale' => ['sometimes', 'string', 'in:en,am'],
        ];
    }
}
