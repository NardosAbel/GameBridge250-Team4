<?php

namespace App\Http\Requests\Ticketing;

use Illuminate\Foundation\Http\FormRequest;

class TransferTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'recipient_email' => ['required_without:recipient_id', 'nullable', 'email', 'exists:users,email'],
            'recipient_id' => ['required_without:recipient_email', 'nullable', 'exists:users,id'],
        ];
    }
}
