<?php

namespace App\Http\Requests\Payments;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Ticketing\Models\TicketOrder;
use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $order = TicketOrder::query()->find($this->input('order_id'));
        if ($order === null) {
            return true;
        }

        return $order->user_id === $user->id || $user->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:ticket_orders,id'],
            'provider' => ['required', 'string', 'in:telebirr,chapa,mpesa,card,bank_transfer,manual'],
            'phone' => ['nullable', 'string', 'max:20'],
            'return_url' => ['nullable', 'url'],
        ];
    }
}
