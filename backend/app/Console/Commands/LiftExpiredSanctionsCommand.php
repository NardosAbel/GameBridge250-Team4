<?php

namespace App\Console\Commands;

use App\Domain\Identity\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Console\Command;

class LiftExpiredSanctionsCommand extends Command
{
    protected $signature = 'sanctions:lift-expired';

    protected $description = 'Restore users whose suspension or mute window has ended';

    public function handle(): int
    {
        $restored = User::query()
            ->where('status', UserStatusEnum::SUSPENDED->value)
            ->whereNotNull('suspended_until')
            ->where('suspended_until', '<=', now())
            ->update([
                'status' => UserStatusEnum::ACTIVE->value,
                'suspended_until' => null,
            ]);

        $unmuted = User::query()
            ->whereNotNull('muted_until')
            ->where('muted_until', '<=', now())
            ->update(['muted_until' => null]);

        $this->info("Restored {$restored} suspended accounts and cleared {$unmuted} mutes.");

        return self::SUCCESS;
    }
}
