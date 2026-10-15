<?php

namespace App\Http\Requests\Ticketing;

use Illuminate\Foundation\Http\FormRequest;

class ScanTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'qr_payload' => ['required'], // String json or array { ref, sig }
            'venue_id' => ['nullable', 'exists:venues,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'scanned_at' => ['nullable', 'date'],
        ];
    }
}
