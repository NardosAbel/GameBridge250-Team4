<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['nullable', 'exists:teams,id'],
            'invitation_code' => ['nullable', 'string', 'max:100'],
            'qualification_score' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
