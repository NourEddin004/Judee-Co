<?php

namespace App\Domain\Monitoring;

/** How full the disk is that holds the site's files: photos, logs and the database's server. */
class DiskUsage
{
    public function __construct(private readonly string $path) {}

    public function usedPercent(): float
    {
        $total = (float) disk_total_space($this->path);

        return $total > 0 ? round(100 * ($total - $this->freeBytes()) / $total, 1) : 0.0;
    }

    public function freeBytes(): float
    {
        return (float) disk_free_space($this->path);
    }
}
