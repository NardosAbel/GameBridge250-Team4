<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class OpenDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dispute', $this->route('match'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'evidence_urls' => ['nullable', 'array'],
            'evidence_urls.*' => ['string', 'max:500'],
        ];
    }
}
