<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('resolve', $this->route('dispute'));
    }

    public function rules(): array
    {
        return [
            'action_taken' => ['required', 'in:confirm_result,overturn_result,rematch,disqualify_participant'],
            'resolution_notes' => ['required', 'string', 'max:1000'],
            'participant1_score' => ['nullable', 'integer', 'min:0'],
            'participant2_score' => ['nullable', 'integer', 'min:0'],
            'winner_id' => ['nullable', 'integer'],
            'disqualified_id' => ['nullable', 'integer'],
        ];
    }
}
