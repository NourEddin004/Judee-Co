<?php

namespace App\Models\Concerns;

use App\Domain\Activity\ActivityLogger;
use Illuminate\Support\Facades\Auth;

/**
 * Every change an admin makes to this model is written to the activity log with
 * who made it and when (REQ-USR-11, REQ-USR-12). Seeders and customers are not logged.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->logActivity('created', $model->loggableValues($model->getAttributes())));
        static::updated(function ($model) {
            $changes = $model->loggableValues($model->getChanges());

            if ($changes !== []) {
                $model->logActivity('updated', $changes);
            }
        });
        static::deleted(fn ($model) => $model->logActivity('deleted'));
    }

    protected function logActivity(string $action, array $changes = []): void
    {
        if (Auth::check()) {
            app(ActivityLogger::class)->record($action, $this, $changes);
        }
    }

    /** @return array<string, mixed> */
    protected function loggableValues(array $values): array
    {
        $hidden = [...$this->activityLogHidden(), 'created_at', 'updated_at', $this->getKeyName()];

        return array_diff_key($values, array_flip($hidden));
    }

    /** @return list<string> */
    public function activityLogHidden(): array
    {
        return [];
    }
}
