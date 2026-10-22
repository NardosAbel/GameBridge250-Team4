<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\PromoCode;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReleaseTicketInventoryAction
{
    /**
     * Return reserved inventory for an order exactly once.
     *
     * Idempotency is the nullable inventory_released_at timestamp, claimed inside
     * the same row lock as the quantity change. Buyer-editable notes are ignored.
     */
    public function execute(TicketOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $order = TicketOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->inventory_released_at !== null) {
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

            $claimed = TicketOrder::whereKey($order->id)
                ->whereNull('inventory_released_at')
                ->update(['inventory_released_at' => now()]);

            if ($claimed !== 1) {
                throw new RuntimeException('Inventory release could not claim the order after adjusting stock.');
            }
        });
    }
}
