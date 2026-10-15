<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Ticketing\Models\PromoCode;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketOrderItem;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateTicketOrderAction
{
    /**
     * Create an order with transactional inventory locking to prevent overselling.
     *
     * @param  array<array{ticket_type_id: int, quantity: int}>  $items
     */
    public function execute(User $buyer, array $items, array $data = []): TicketOrder
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Order must contain at least one ticket item.');
        }

        return DB::transaction(function () use ($buyer, $items, $data) {
            $subtotal = 0.00;
            $serviceFeeTotal = 0.00;
            $orderItemsData = [];
            $eventId = $data['event_id'] ?? null;
            $tournamentId = $data['tournament_id'] ?? null;
            $currency = 'ETB';

            // 1. Process items with lockForUpdate to prevent race conditions and overselling
            foreach ($items as $item) {
                $typeId = (int) $item['ticket_type_id'];
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                $ticketType = TicketType::where('id', $typeId)->lockForUpdate()->firstOrFail();

                if (! $ticketType->isAvailable()) {
                    throw new InvalidArgumentException("Ticket '{$ticketType->name}' is currently unavailable or sold out.");
                }

                if ($quantity < $ticketType->min_per_order) {
                    throw new InvalidArgumentException("Minimum purchase for '{$ticketType->name}' is {$ticketType->min_per_order}.");
                }

                if ($quantity > $ticketType->max_per_order) {
                    throw new InvalidArgumentException("Maximum purchase for '{$ticketType->name}' is {$ticketType->max_per_order}.");
                }

                if ($ticketType->remaining_quantity < $quantity) {
                    throw new InvalidArgumentException("Not enough tickets available for '{$ticketType->name}'. Only {$ticketType->remaining_quantity} left.");
                }

                // Deduct inventory immediately inside transaction
                $ticketType->increment('quantity_sold', $quantity);

                $itemSubtotal = round($quantity * (float) $ticketType->price, 2);
                $itemFee = round($quantity * (float) $ticketType->service_fee, 2);

                $subtotal += $itemSubtotal;
                $serviceFeeTotal += $itemFee;
                $currency = $ticketType->currency;

                if (! $eventId && $ticketType->event_id) {
                    $eventId = $ticketType->event_id;
                }
                if (! $tournamentId && $ticketType->tournament_id) {
                    $tournamentId = $ticketType->tournament_id;
                }

                $orderItemsData[] = [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => $quantity,
                    'unit_price' => $ticketType->price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 2. Promo Code Calculation
            $discountAmount = 0.00;
            $promoCode = null;
            if (! empty($data['promo_code'])) {
                $promoCode = PromoCode::where('code', strtoupper(trim($data['promo_code'])))->first();
                if ($promoCode && $promoCode->isValid()) {
                    $discountAmount = $promoCode->calculateDiscount($subtotal);
                    $promoCode->increment('uses_count');
                }
            }

            $totalAmount = max(0.00, round(($subtotal - $discountAmount) + $serviceFeeTotal, 2));

            // Status: If free RSVP/complimentary, can be immediately completed, else pending payment
            $status = ($totalAmount == 0.00) ? 'completed' : 'pending';

            $order = TicketOrder::create([
                'user_id' => $buyer->id,
                'event_id' => $eventId,
                'tournament_id' => $tournamentId,
                'total_amount' => $totalAmount,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'service_fee' => $serviceFeeTotal,
                'currency' => $currency,
                'status' => $status,
                'payment_method' => $data['payment_method'] ?? ($totalAmount == 0.00 ? 'manual' : null),
                'promo_code_id' => $promoCode?->id,
                'billing_details' => $data['billing_details'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($orderItemsData as $orderItem) {
                TicketOrderItem::create(array_merge($orderItem, [
                    'ticket_order_id' => $order->id,
                ]));
            }

            // If free order, automatically issue tickets right away!
            if ($status === 'completed') {
                app(IssueTicketsAction::class)->execute($order);
            }

            AuditLogger::log(
                action: 'ticket_order.created',
                user: $buyer,
                entityType: 'ticket_order',
                entityId: $order->id,
                metadata: [
                    'total_amount' => $order->total_amount,
                    'items_count' => count($orderItemsData),
                    'status' => $order->status,
                ]
            );

            return $order;
        });
    }
}
