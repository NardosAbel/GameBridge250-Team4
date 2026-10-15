<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class OverrideMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('overrideResult', $this->route('match'));
    }

    public function rules(): array
    {
        return [
            'participant1_score' => ['required', 'integer', 'min:0'],
            'participant2_score' => ['required', 'integer', 'min:0'],
            'winner_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
