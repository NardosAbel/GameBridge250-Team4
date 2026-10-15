<?php

namespace App\Domain\Payments\Services;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Payments\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayoutService
{
    /**
     * Request a payout for an organizer's event or tournament proceeds.
     */
    public function requestPayout(
        User $organizer,
        float $grossAmount,
        string $accountNumber,
        string $provider = 'telebirr',
        ?int $eventId = null,
        ?int $tournamentId = null
    ): Payout {
        if ($grossAmount <= 0) {
            throw new InvalidArgumentException('Payout amount must be greater than zero.');
        }

        // 5% standard transparent platform fee
        $platformFeeRate = 0.05;
        $platformFee = round($grossAmount * $platformFeeRate, 2);
        $netPayout = max(0.00, round($grossAmount - $platformFee, 2));

        return DB::transaction(function () use ($organizer, $netPayout, $grossAmount, $platformFee, $accountNumber, $provider, $eventId, $tournamentId) {
            $payout = Payout::create([
                'user_id' => $organizer->id,
                'event_id' => $eventId,
                'tournament_id' => $tournamentId,
                'amount' => $netPayout,
                'gross_amount' => $grossAmount,
                'platform_fee' => $platformFee,
                'currency' => 'ETB',
                'provider' => $provider,
                'account_number' => $accountNumber,
                'status' => 'pending',
            ]);

            AuditLogger::log(
                action: 'payout.requested',
                user: $organizer,
                entityType: 'payout',
                entityId: $payout->id,
                metadata: [
                    'gross_amount' => $grossAmount,
                    'platform_fee' => $platformFee,
                    'net_amount' => $netPayout,
                    'account_number' => $accountNumber,
                ]
            );

            return $payout;
        });
    }

    /**
     * Process an organizer payout by admin.
     */
    public function processPayout(Payout $payout, User $admin, bool $approve): Payout
    {
        return DB::transaction(function () use ($payout, $admin, $approve) {
            $status = $approve ? 'paid' : 'rejected';

            $payout->update([
                'status' => $status,
                'processed_at' => $approve ? now() : null,
            ]);

            AuditLogger::log(
                action: "payout.{$status}",
                user: $admin,
                entityType: 'payout',
                entityId: $payout->id,
                metadata: [
                    'status' => $status,
                    'amount' => $payout->amount,
                ]
            );

            return $payout;
        });
    }
}
