<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class TeamInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['nullable', 'string', 'in:invitation,join_request'],
            'role' => ['nullable', 'string', 'in:owner,captain,coach,manager,player,substitute'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
