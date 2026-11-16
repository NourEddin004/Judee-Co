<?php

namespace App\Console\Commands;

use App\Domain\Monitoring\DiskUsage;
use App\Models\User;
use App\Notifications\DiskAlmostFull;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/** Run every hour by the scheduler (routes/console.php). Emails the owner once a day while the disk stays full. */
#[Signature('jude:check-disk')]
#[Description("Email the owner when the server's disk is 80% full")]
class CheckDiskSpace extends Command
{
    private const SENT = 'disk-alert-sent';

    public function handle(DiskUsage $disk): int
    {
        $used = $disk->usedPercent();
        $this->line("Disk {$used}% full.");

        if ($used < config('store.disk_alert_percent')) {
            Cache::forget(self::SENT);   // the next time it fills up, the owner hears at once

            return self::SUCCESS;
        }

        $owner = User::query()->where('role', User::OWNER)->orderBy('id')->first();

        if ($owner && Cache::add(self::SENT, true, now()->addDay())) {
            $owner->notify(new DiskAlmostFull($used, $disk->freeBytes()));
            $this->warn('The owner has been emailed.');
        }

        return self::SUCCESS;
    }
}
