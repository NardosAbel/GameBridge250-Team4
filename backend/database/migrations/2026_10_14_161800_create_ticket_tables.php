<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('name');
            $table->string('category', 30)->default('general_admission'); // ga, vip, student, player_pass, team_pass, staff, press, early_bird
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('ETB');
            $table->unsignedInteger('quantity_total');
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event_id', 'is_active']);
            $table->index(['tournament_id', 'is_active']);
        });

        Schema::create('ticket_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('ETB');
            $table->string('status', 30)->default('pending'); // pending, completed, failed, refunded
            $table->string('idempotency_key')->unique();
            $table->string('payment_method', 30)->nullable(); // telebirr, chapa, mpesa, card, bank_transfer, manual
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ticket_order_id')->constrained('ticket_orders')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ticket_code', 32)->unique();
            $table->string('qr_token_hash', 64)->unique();
            $table->string('status', 30)->default('valid'); // valid, used, expired, revoked, refunded
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index(['user_id', 'status']);
        });

        Schema::create('ticket_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('scanner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('status', 30)->default('valid'); // valid, duplicate, invalid, offline_synced
            $table->timestamp('scanned_at');
            $table->string('device_id')->nullable();
            $table->json('raw_sync_data')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_scans');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_orders');
        Schema::dropIfExists('ticket_types');
    }
};
