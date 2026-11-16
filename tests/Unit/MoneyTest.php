<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    protected bool $seed = false;

    #[Test]
    public function req_dmo_39_and_42_jod_prices_drop_decimals_only_when_whole(): void
    {
        $this->assertSame('17 JD', Money::jod('17.00'));
        $this->assertSame('12.50 JD', Money::jod('12.5'));
        $this->assertSame('18 JD', Money::jod(18));
    }

    #[Test]
    public function req_dmo_43_a_missing_price_shows_price_to_be_confirmed(): void
    {
        $this->assertSame('Price to be confirmed', Money::jod(null));
    }
}
