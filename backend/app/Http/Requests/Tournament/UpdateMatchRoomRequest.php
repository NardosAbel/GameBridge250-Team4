<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $match = $this->route('match');
        $user = $this->user();
        $tournament = $match->tournament;

        return $user->hasRole('super-admin')
            || $tournament->organizer_id === $user->id
            || $tournament->staff()->where('user_id', $user->id)->exists()
            || $match->referee_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'string', 'max:100'],
            'room_password' => ['nullable', 'string', 'max:100'],
            'room_server' => ['nullable', 'string', 'max:100'],
            'room_instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
