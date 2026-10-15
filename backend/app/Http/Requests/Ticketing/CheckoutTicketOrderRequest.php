<?php

namespace App\Http\Requests\Ticketing;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutTicketOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['nullable', 'exists:events,id'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'exists:ticket_types,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'in:telebirr,chapa,mpesa,card,bank_transfer,manual'],
            'billing_details' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
