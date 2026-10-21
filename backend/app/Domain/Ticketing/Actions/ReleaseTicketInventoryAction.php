<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\PromoCode;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Facades\DB;

class ReleaseTicketInventoryAction
{
    /**
     * Return reserved inventory for an order exactly once.
     */
    public function execute(TicketOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = TicketOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $notes = $order->notes ?? '';
            if (str_contains($notes, '[inventory_released]')) {
                return;
            }

            $order->loadMissing('items');

            foreach ($order->items as $item) {
                $type = TicketType::whereKey($item->ticket_type_id)->lockForUpdate()->first();
                if (! $type) {
                    continue;
                }

                $type->quantity_sold = max(0, (int) $type->quantity_sold - (int) $item->quantity);
                $type->save();
            }

            if ($order->promo_code_id) {
                $promo = PromoCode::whereKey($order->promo_code_id)->lockForUpdate()->first();
                if ($promo && (int) $promo->uses_count > 0) {
                    $promo->decrement('uses_count');
                }
            }

            $order->update([
                'notes' => trim($notes.' [inventory_released]'),
            ]);
        });
    }
}
