<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submitResult', $this->route('match'));
    }

    public function rules(): array
    {
        return [
            'participant1_score' => ['required', 'integer', 'min:0'],
            'participant2_score' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'game_metadata' => ['nullable', 'array'],
            'evidence' => ['nullable', 'array'],
            'evidence.*.type' => ['required', 'in:screenshot,clip,match_id,note'],
            'evidence.*.url' => ['required', 'string', 'max:500'],
            'evidence.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
