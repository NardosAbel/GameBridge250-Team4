<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create promo_codes table
        if (! Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->nullOnDelete();
                $table->string('code', 50)->unique();
                $table->string('discount_type', 20)->default('percentage'); // percentage, fixed
                $table->decimal('discount_value', 10, 2);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('uses_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['code', 'is_active']);
            });
        }

        // 2. Enhance ticket_types table
        Schema::table('ticket_types', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_types', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('ticket_types', 'sales_start_at')) {
                $table->timestamp('sales_start_at')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('ticket_types', 'sales_end_at')) {
                $table->timestamp('sales_end_at')->nullable()->after('sales_start_at');
            }
            if (! Schema::hasColumn('ticket_types', 'min_per_order')) {
                $table->unsignedInteger('min_per_order')->default(1)->after('quantity_sold');
            }
            if (! Schema::hasColumn('ticket_types', 'max_per_order')) {
                $table->unsignedInteger('max_per_order')->default(10)->after('min_per_order');
            }
            if (! Schema::hasColumn('ticket_types', 'service_fee')) {
                $table->decimal('service_fee', 10, 2)->default(0.00)->after('max_per_order');
            }
            if (! Schema::hasColumn('ticket_types', 'settings')) {
                $table->json('settings')->nullable()->after('service_fee');
            }
        });

        // 3. Enhance ticket_orders table
        Schema::table('ticket_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_orders', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('user_id')->constrained('events')->nullOnDelete();
            }
            if (! Schema::hasColumn('ticket_orders', 'tournament_id')) {
                $table->foreignId('tournament_id')->nullable()->after('event_id')->constrained('tournaments')->nullOnDelete();
            }
            if (! Schema::hasColumn('ticket_orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0.00)->after('total_amount');
            }
            if (! Schema::hasColumn('ticket_orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0.00)->after('subtotal');
            }
            if (! Schema::hasColumn('ticket_orders', 'service_fee')) {
                $table->decimal('service_fee', 10, 2)->default(0.00)->after('discount_amount');
            }
            if (! Schema::hasColumn('ticket_orders', 'promo_code_id')) {
                $table->foreignId('promo_code_id')->nullable()->after('service_fee')->constrained('promo_codes')->nullOnDelete();
            }
            if (! Schema::hasColumn('ticket_orders', 'billing_details')) {
                $table->json('billing_details')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('ticket_orders', 'notes')) {
                $table->text('notes')->nullable()->after('billing_details');
            }
        });

        // 4. Create ticket_order_items table
        if (! Schema::hasTable('ticket_order_items')) {
            Schema::create('ticket_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_order_id')->constrained('ticket_orders')->cascadeOnDelete();
                $table->foreignId('ticket_type_id')->constrained('ticket_types')->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->timestamps();

                $table->index('ticket_order_id');
            });
        }

        // 5. Enhance tickets table
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('ticket_type_id')->constrained('events')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'tournament_id')) {
                $table->foreignId('tournament_id')->nullable()->after('event_id')->constrained('tournaments')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'venue_id')) {
                $table->foreignId('venue_id')->nullable()->after('tournament_id')->constrained('venues')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'transferred_to_user_id')) {
                $table->foreignId('transferred_to_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'transfer_history')) {
                $table->json('transfer_history')->nullable()->after('transferred_to_user_id');
            }
            if (! Schema::hasColumn('tickets', 'qr_signature')) {
                $table->string('qr_signature', 255)->nullable()->after('qr_token_hash');
            }
            if (! Schema::hasColumn('tickets', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('checked_in_by_id');
            }
        });

        // 6. Create payment_webhooks table for idempotency & reconciliation
        if (! Schema::hasTable('payment_webhooks')) {
            Schema::create('payment_webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 30);
                $table->string('event_type', 100);
                $table->string('provider_reference', 255)->nullable()->index();
                $table->json('payload');
                $table->string('signature', 500)->nullable();
                $table->string('status', 30)->default('pending'); // pending, processed, failed, duplicate
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['provider', 'provider_reference']);
            });
        }

        // 7. Enhance payments table
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('webhook_payload');
            }
            if (! Schema::hasColumn('payments', 'fee')) {
                $table->decimal('fee', 10, 2)->default(0.00)->after('amount');
            }
            if (! Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('metadata');
            }
        });

        // 8. Enhance refunds table
        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'reason_category')) {
                $table->string('reason_category', 50)->nullable()->after('reason');
            }
            if (! Schema::hasColumn('refunds', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reason_category');
            }
            if (! Schema::hasColumn('refunds', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('status');
            }
        });

        // 9. Enhance payouts table
        Schema::table('payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('payouts', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('user_id')->constrained('events')->nullOnDelete();
            }
            if (! Schema::hasColumn('payouts', 'tournament_id')) {
                $table->foreignId('tournament_id')->nullable()->after('event_id')->constrained('tournaments')->nullOnDelete();
            }
            if (! Schema::hasColumn('payouts', 'gross_amount')) {
                $table->decimal('gross_amount', 10, 2)->default(0.00)->after('amount');
            }
            if (! Schema::hasColumn('payouts', 'platform_fee')) {
                $table->decimal('platform_fee', 10, 2)->default(0.00)->after('gross_amount');
            }
            if (! Schema::hasColumn('payouts', 'notes')) {
                $table->text('notes')->nullable()->after('account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropForeign(['tournament_id']);
            $table->dropColumn(['event_id', 'tournament_id', 'gross_amount', 'platform_fee', 'notes']);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['reason_category', 'rejection_reason', 'processed_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['metadata', 'fee', 'paid_at']);
        });

        Schema::dropIfExists('payment_webhooks');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropForeign(['tournament_id']);
            $table->dropForeign(['venue_id']);
            $table->dropForeign(['transferred_to_user_id']);
            $table->dropColumn(['event_id', 'tournament_id', 'venue_id', 'transferred_to_user_id', 'transfer_history', 'qr_signature', 'expires_at']);
        });

        Schema::dropIfExists('ticket_order_items');

        Schema::table('ticket_orders', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropForeign(['tournament_id']);
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['event_id', 'tournament_id', 'subtotal', 'discount_amount', 'service_fee', 'promo_code_id', 'billing_details', 'notes']);
        });

        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn(['description', 'sales_start_at', 'sales_end_at', 'min_per_order', 'max_per_order', 'service_fee', 'settings']);
        });

        Schema::dropIfExists('promo_codes');
    }
};
