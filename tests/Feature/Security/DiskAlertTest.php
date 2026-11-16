<?php

namespace Tests\Feature\Security;

use App\Domain\Monitoring\DiskUsage;
use App\Models\User;
use App\Notifications\DiskAlmostFull;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The owner hears when the server's disk is 80% full, at most once a day (REQ-CST-10). */
class DiskAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->owner = User::factory()->create(['role' => User::OWNER, 'name' => 'Jude', 'email' => 'owner@judee.co']);
    }

    private function diskAt(float $percent): void
    {
        $this->app->instance(DiskUsage::class, new class($percent) extends DiskUsage
        {
            public function __construct(private readonly float $percent) {}

            public function usedPercent(): float
            {
                return $this->percent;
            }

            public function freeBytes(): float
            {
                return (100 - $this->percent) / 100 * 40 * 1024 ** 3;
            }
        });
    }

    #[Test]
    public function req_cst_10_the_owner_is_emailed_when_the_disk_is_80_percent_full(): void
    {
        $this->diskAt(79.9);
        $this->artisan('jude:check-disk')->assertSuccessful();
        Notification::assertNothingSent();

        $this->diskAt(82.5);
        $this->artisan('jude:check-disk')->assertSuccessful();

        Notification::assertSentTo($this->owner, DiskAlmostFull::class, function (DiskAlmostFull $notification) {
            $mail = $notification->toMail($this->owner);
            $this->assertSame("The store's server disk is 82.5% full", $mail->subject);
            $this->assertContains("The disk on the store's server is 82.5% full. 7.0 GB is left.", $mail->introLines);

            return true;
        });
    }

    #[Test]
    public function req_cst_10_once_a_day_while_it_stays_full_and_again_after_it_was_freed(): void
    {
        $this->diskAt(85);
        $this->artisan('jude:check-disk');
        $this->artisan('jude:check-disk');
        Notification::assertSentToTimes($this->owner, DiskAlmostFull::class, 1);

        $this->travel(25)->hours();
        $this->artisan('jude:check-disk');
        Notification::assertSentToTimes($this->owner, DiskAlmostFull::class, 2);

        // Freed, then full again within the day: the owner hears at once.
        $this->diskAt(60);
        $this->artisan('jude:check-disk');
        $this->diskAt(90);
        $this->artisan('jude:check-disk');
        Notification::assertSentToTimes($this->owner, DiskAlmostFull::class, 3);
    }

    #[Test]
    public function req_cst_10_the_real_disk_is_read(): void
    {
        $disk = app(DiskUsage::class);

        $this->assertGreaterThan(0, $disk->usedPercent());
        $this->assertLessThan(100, $disk->usedPercent());
        $this->assertGreaterThan(0, $disk->freeBytes());
    }
}
