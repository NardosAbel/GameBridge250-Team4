<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:ticket_orders,id'],
            'provider' => ['required', 'string', 'in:telebirr,chapa,mpesa,card,bank_transfer,manual'],
            'phone' => ['nullable', 'string', 'max:20'],
            'return_url' => ['nullable', 'url'],
        ];
    }
}
