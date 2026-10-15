<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class RequestRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
            'reason_category' => ['nullable', 'string', 'in:event_cancelled,event_rescheduled,attendee_unavailable,duplicate_purchase,other'],
        ];
    }
}
