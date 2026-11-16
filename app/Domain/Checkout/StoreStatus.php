<?php

namespace App\Domain\Checkout;

/** Whether the store takes orders right now (REQ-CHK-17). */
interface StoreStatus
{
    public function isOpen(): bool;
}
