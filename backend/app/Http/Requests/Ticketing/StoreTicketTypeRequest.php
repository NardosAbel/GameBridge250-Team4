<?php

namespace App\Http\Requests\Ticketing;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Venues\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        $event = Event::query()->find($this->input('event_id'));
        $tournament = Tournament::query()->find($this->input('tournament_id'));

        $ownsEvent = $event !== null && $event->organizer_id === $user->id;
        $ownsTournament = $tournament !== null && $tournament->organizer_id === $user->id;
        $bothProvided = $event !== null && $tournament !== null;
        $linked = ! $bothProvided || (int) ($event->tournament_id ?? 0) === (int) $tournament->id;

        return ($ownsEvent || $ownsTournament) && $linked;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['nullable', 'exists:events,id', 'required_without:tournament_id'],
            'tournament_id' => ['nullable', 'exists:tournaments,id', 'required_without:event_id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'string', 'in:free_rsvp,general_admission,vip,student,player_pass,team_pass,staff,press,vendor,early_bird,group,complimentary'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'quantity_total' => ['required', 'integer', 'min:1'],
            'min_per_order' => ['nullable', 'integer', 'min:1'],
            'max_per_order' => ['nullable', 'integer', 'min:1'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'sales_start_at' => ['nullable', 'date'],
            'sales_end_at' => ['nullable', 'date'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
