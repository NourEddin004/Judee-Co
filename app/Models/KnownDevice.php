<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A browser an admin account has logged in from before (REQ-SEC-23). */
class KnownDevice extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'user_agent', 'ip_address', 'last_seen_at'];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
