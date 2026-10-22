<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('ticket_orders', 'inventory_released_at')) {
                $table->timestamp('inventory_released_at')->nullable()->after('notes');
            }
        });

        // Do not copy `[inventory_released]` out of notes. Checkout stores
        // buyer-supplied notes, so that substring is not proof that quantity_sold
        // was returned. Backfilling it would skip a real release for forged markers
        // and could also hide orders the old action actually released. quantity_sold
        // is left unchanged; reconcile those historical rows explicitly if needed.
    }

    public function down(): void
    {
        Schema::table('ticket_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('ticket_orders', 'inventory_released_at')) {
                $table->dropColumn('inventory_released_at');
            }
        });
    }
};
