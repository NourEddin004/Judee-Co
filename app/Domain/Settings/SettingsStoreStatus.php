<?php

namespace App\Domain\Settings;

use App\Domain\Checkout\StoreStatus;

/** Open unless the owner switched on "Pause the store" (REQ-CMS-36, REQ-CMS-37). */
class SettingsStoreStatus implements StoreStatus
{
    public function __construct(private readonly StoreSettings $settings) {}

    public function isOpen(): bool
    {
        return ! $this->settings->isPaused();
    }
}
