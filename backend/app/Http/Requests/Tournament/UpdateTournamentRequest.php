<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tournament'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'in:public,unlisted,private'],
            'format' => ['nullable', 'in:single_elimination,double_elimination,round_robin,swiss,group_knockout'],
            'match_format' => ['nullable', 'in:bo1,bo3,bo5'],
            'event_type' => ['nullable', 'in:online,offline,hybrid'],
            'entry_type' => ['nullable', 'in:free,paid,invitation,qualification'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'prize_pool' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:2', 'max:512'],
            'rules' => ['nullable', 'string'],
            'eligibility_rules' => ['nullable', 'array'],
            'points_distribution' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'registration_start_at' => ['nullable', 'date'],
            'registration_end_at' => ['nullable', 'date'],
            'check_in_start_at' => ['nullable', 'date'],
            'check_in_end_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'banner_url' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'theme_color' => ['nullable', 'string', 'max:30'],
            'stream_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
