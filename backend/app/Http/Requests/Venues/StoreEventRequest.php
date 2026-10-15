<?php

namespace App\Http\Requests\Venues;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'venue_id' => ['nullable', 'exists:venues,id'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'title' => ['required', 'string', 'max:255'],
            'banner_url' => ['nullable', 'url'],
            'description' => ['nullable', 'string', 'max:3000'],
            'event_type' => ['nullable', 'string', 'in:tournament,lan_party,community_scrim,viewing_party,workshop'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'recurring_pattern' => ['nullable', 'array'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:draft,published,live,completed,cancelled'],
        ];
    }
}
