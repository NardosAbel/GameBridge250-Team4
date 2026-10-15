<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

class CreateTicketTypeAction
{
    public function execute(User $creator, array $data): TicketType
    {
        $ticketType = TicketType::create([
            'tournament_id' => $data['tournament_id'] ?? null,
            'event_id' => $data['event_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'general_admission',
            'price' => $data['price'] ?? 0.00,
            'currency' => $data['currency'] ?? 'ETB',
            'quantity_total' => (int) $data['quantity_total'],
            'quantity_sold' => 0,
            'min_per_order' => $data['min_per_order'] ?? 1,
            'max_per_order' => $data['max_per_order'] ?? 10,
            'service_fee' => $data['service_fee'] ?? 0.00,
            'sales_start_at' => $data['sales_start_at'] ?? null,
            'sales_end_at' => $data['sales_end_at'] ?? null,
            'settings' => $data['settings'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        AuditLogger::log(
            action: 'ticket_type.created',
            user: $creator,
            entityType: 'ticket_type',
            entityId: $ticketType->id,
            metadata: [
                'name' => $ticketType->name,
                'category' => $ticketType->category,
                'price' => $ticketType->price,
                'quantity_total' => $ticketType->quantity_total,
            ]
        );

        return $ticketType;
    }
}
