<?php

namespace App\Domain\Activity;

use App\Models\ActivityLog;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DatabaseActivityLogger implements ActivityLogger
{
    /** Long texts are shortened so one edit never bloats the log. */
    private const MAX_VALUE_LENGTH = 160;

    public function __construct(private readonly Auth $auth) {}

    public function record(string $action, Model $subject, array $changes = []): void
    {
        ActivityLog::create([
            'user_id' => $this->auth->guard()->id(),
            'action' => $action,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'changes' => $changes === [] ? null : array_map($this->shorten(...), $changes),
        ]);
    }

    private function shorten(mixed $value): mixed
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return is_string($value) ? Str::limit($value, self::MAX_VALUE_LENGTH) : $value;
    }
}
