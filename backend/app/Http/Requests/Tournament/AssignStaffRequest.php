<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class AssignStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignStaff', $this->route('tournament'));
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:admin,co_organizer,referee,scorekeeper,caster,observer,support'],
        ];
    }
}
