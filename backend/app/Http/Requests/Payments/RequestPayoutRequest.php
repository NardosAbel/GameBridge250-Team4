<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class RequestPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'gross_amount' => ['required', 'numeric', 'min:50'],
            'account_number' => ['required', 'string', 'max:50'],
            'provider' => ['required', 'string', 'in:telebirr,cbe,awash,mpesa'],
            'event_id' => ['nullable', 'exists:events,id'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
        ];
    }
}
