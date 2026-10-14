<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecruitmentPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:lft,lfp'],
            'game_id' => ['required', 'exists:games,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'rank_tier' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:100'],
            'university' => ['nullable', 'string', 'max:100'],
            'age_group' => ['nullable', 'string', 'max:50'],
            'availability' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:50'],
        ];
    }
}
