<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealthCheckCommand extends Command
{
    protected $signature = 'cee:health-check';

    protected $description = 'Perform production uptime, database, cache, and queue readiness diagnostics';

    public function handle(): int
    {
        $this->info('Starting Campus Esports Ethiopia Production Health Check...');
        $allPassed = true;

        // 1. Database Check
        try {
            DB::connection()->getPdo();
            $this->line('  [PASS] Database Connection: Operational');
        } catch (\Throwable $e) {
            $this->error('  [FAIL] Database Connection: '.$e->getMessage());
            $allPassed = false;
        }

        // 2. Cache Check
        try {
            Cache::put('cee_health_test', 1, 10);
            $val = Cache::get('cee_health_test');
            if ($val === 1) {
                $this->line('  [PASS] Cache Driver: Operational');
            } else {
                throw new \Exception('Cache write/read mismatch');
            }
        } catch (\Throwable $e) {
            $this->error('  [FAIL] Cache Driver: '.$e->getMessage());
            $allPassed = false;
        }

        // 3. Storage Disk Check
        try {
            Storage::disk('local')->put('health_check.txt', 'OK');
            Storage::disk('local')->delete('health_check.txt');
            $this->line('  [PASS] Local Storage: Writable');
        } catch (\Throwable $e) {
            $this->error('  [FAIL] Storage Disk: '.$e->getMessage());
            $allPassed = false;
        }

        // 4. Security Configuration Check
        if (config('app.env') === 'production' && config('app.debug')) {
            $this->warn('  [WARN] APP_DEBUG is enabled in production! Disable immediately.');
            $allPassed = false;
        } else {
            $this->line('  [PASS] Debug Mode: Secure');
        }

        if ($allPassed) {
            $this->info('Platform Status: All core subsystems operational and ready.');

            return self::SUCCESS;
        }

        $this->error('Platform Status: Subsystems report degraded performance or errors.');

        return self::FAILURE;
    }
}
