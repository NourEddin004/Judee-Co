<?php

namespace App\Domain\Settings;

use App\Models\Setting;
use App\Support\StoreCache;
use Illuminate\Support\Facades\Storage;

/** Site settings (logo, contact links, USD rate, store open/paused), cached (REQ-DB-25). */
class StoreSettings
{
    private ?array $values = null;

    public function __construct(private readonly StoreCache $cache) {}

    public function get(string $key, ?string $default = null): ?string
    {
        $this->values ??= $this->cache->remember('settings', fn () => Setting::query()->pluck('value', 'key')->all());

        return $this->values[$key] ?? $default;
    }

    public function usdRate(): float
    {
        return (float) $this->get('usd_rate', '1.41');
    }

    public function isPaused(): bool
    {
        return $this->get('store_paused') === '1';
    }

    public function logoUrl(): ?string
    {
        $logo = $this->get('logo');

        return $logo ? Storage::disk('public')->url($logo) : null;
    }
}
