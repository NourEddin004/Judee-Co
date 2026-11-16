<?php

namespace App\Domain\Activity;

use Illuminate\Database\Eloquent\Model;

/** Records who changed what in the admin panel, and when (REQ-USR-11, REQ-USR-12). */
interface ActivityLogger
{
    /** @param  array<string, mixed>  $changes */
    public function record(string $action, Model $subject, array $changes = []): void;
}
