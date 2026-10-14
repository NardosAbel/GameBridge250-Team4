<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $team = $this->route('team');
        $teamId = is_object($team) ? $team->id : $team;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', "unique:teams,name,{$teamId}"],
            'tag' => ['sometimes', 'required', 'string', 'min:2', 'max:10'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'game_id' => ['nullable', 'exists:games,id'],
            'university' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'recruitment_status' => ['nullable', 'string', 'in:open,closed,by_application'],
            'logo_url' => ['nullable', 'string', 'url', 'max:255'],
            'banner_url' => ['nullable', 'string', 'url', 'max:255'],
            'achievements' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
