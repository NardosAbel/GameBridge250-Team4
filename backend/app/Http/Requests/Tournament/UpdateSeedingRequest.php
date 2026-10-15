<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeedingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tournament'));
    }

    public function rules(): array
    {
        return [
            'seeds' => ['required', 'array'],
            'seeds.*' => ['required', 'integer', 'min:1'],
        ];
    }
}
