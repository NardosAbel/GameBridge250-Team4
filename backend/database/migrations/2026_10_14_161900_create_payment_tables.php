<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ticket_order_id')->nullable()->constrained('ticket_orders')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ETB');
            $table->string('provider', 30); // telebirr, chapa, mpesa, card, bank_transfer, manual
            $table->string('provider_reference')->nullable()->index();
            $table->string('status', 30)->default('pending'); // pending, successful, failed, cancelled
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('webhook_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('ticket_order_id')->nullable()->constrained('ticket_orders')->nullOnDelete();
            $table->foreignId('requested_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('processed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->text('reason');
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, processed
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ETB');
            $table->string('provider', 30);
            $table->string('account_number');
            $table->string('status', 30)->default('pending'); // pending, processing, paid, rejected
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
