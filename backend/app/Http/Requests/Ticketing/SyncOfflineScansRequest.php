<?php

namespace App\Http\Requests\Ticketing;

use Illuminate\Foundation\Http\FormRequest;

class SyncOfflineScansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'scans' => ['required', 'array', 'min:1'],
            'scans.*.qr_payload' => ['required'],
            'scans.*.scanned_at' => ['required', 'date'],
            'scans.*.venue_id' => ['nullable', 'exists:venues,id'],
            'scans.*.event_id' => ['nullable', 'exists:events,id'],
            'scans.*.device_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
