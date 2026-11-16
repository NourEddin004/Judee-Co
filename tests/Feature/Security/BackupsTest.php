<?php

namespace Tests\Feature\Security;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

/**
 * Nightly encrypted backups of the database and the photos, kept 14 days (REQ-SAF-01 to 03,
 * REQ-SEC-28), the restore (REQ-SAF-04) and the schedule that runs them (REQ-DB-16).
 * The full round trip with MySQL's own mysqldump was also run by hand (docs/m4-review-notes.md).
 */
class BackupsTest extends TestCase
{
    use RefreshDatabase;

    private string $backups;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backups = storage_path('framework/testing/backups');
        File::deleteDirectory($this->backups);
        config([
            'filesystems.disks.backups' => ['driver' => 'local', 'root' => $this->backups, 'throw' => true],
            'backup.backup.password' => 'test-archive-password',
            'backup.backup.tries' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backups);
        putenv('MIGRATE_DB_USERNAME');
        putenv('MIGRATE_DB_PASSWORD');
        parent::tearDown();
    }

    /** @return list<string> */
    private function zips(): array
    {
        return File::glob("{$this->backups}/jude-boutique/*.zip");
    }

    private function scheduled(string $command): ?Event
    {
        return collect(app(Schedule::class)->events())->first(fn (Event $event) => str_ends_with((string) $event->command, $command));
    }

    #[Test]
    public function req_saf_01_req_saf_02_the_database_and_the_photos_are_backed_up_every_night(): void
    {
        $this->assertSame(['mysql'], config('backup.backup.source.databases'));
        $this->assertSame([realpath(storage_path('app/public'))], config('backup.backup.source.files.include'));
        $this->assertSame(['backups'], config('backup.backup.destination.disks'));
        $this->assertSame('30 1 * * *', $this->scheduled('backup:run')->expression);

        // The photo half for real (the database half needs MySQL's mysqldump, run by hand).
        $this->artisan('backup:run --only-files --disable-notifications')->assertSuccessful();

        $zip = new ZipArchive;
        $zip->open($this->zips()[0]);
        $names = array_map(fn ($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1));
        $this->assertContains('public/brand/logo.png', $names);
        $this->assertNotEmpty(preg_grep('#^public/products/[a-z0-9-]+\.webp$#', $names));
    }

    #[Test]
    public function req_saf_01_on_the_live_site_backups_go_to_another_server(): void
    {
        $_ENV['BACKUP_SFTP_HOST'] = $_SERVER['BACKUP_SFTP_HOST'] = 'backup.example.com';
        try {
            $disk = (require config_path('filesystems.php'))['disks']['backups'];
        } finally {
            unset($_ENV['BACKUP_SFTP_HOST'], $_SERVER['BACKUP_SFTP_HOST']);
        }

        $this->assertSame(['sftp', 'backup.example.com'], [$disk['driver'], $disk['host']]);
        $settings = file_get_contents(base_path('deploy/env.production.example'));
        foreach (['BACKUP_SFTP_HOST=', 'BACKUP_SFTP_USERNAME=', 'BACKUP_SFTP_PRIVATE_KEY=', 'BACKUP_ARCHIVE_PASSWORD=', 'BACKUP_NOTIFY_EMAIL='] as $line) {
            $this->assertStringContainsString("\n{$line}", $settings);
        }
    }

    #[Test]
    public function req_sec_28_every_file_in_a_backup_is_encrypted_with_aes_256(): void
    {
        $this->artisan('backup:run --only-files --disable-notifications')->assertSuccessful();

        $zip = new ZipArchive;
        $zip->open($this->zips()[0]);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            if (! str_ends_with($entry['name'], '/')) {
                $this->assertSame(ZipArchive::EM_AES_256, $entry['encryption_method'], $entry['name']);
            }
        }
        $this->assertFalse($zip->getFromName('public/brand/logo.png'), 'Readable without the password');
        $zip->setPassword('test-archive-password');
        $this->assertNotFalse($zip->getFromName('public/brand/logo.png'));
    }

    #[Test]
    public function req_sec_28_without_a_password_no_backup_is_made(): void
    {
        config(['backup.backup.password' => '']);

        $this->artisan('backup:run --only-files --disable-notifications')->assertFailed();

        $this->assertSame([], $this->zips());
    }

    #[Test]
    public function req_saf_03_each_backup_is_kept_for_14_days(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        File::ensureDirectoryExists("{$this->backups}/jude-boutique");
        foreach ([1, 13, 15, 20] as $daysAgo) {
            File::put("{$this->backups}/jude-boutique/".now()->subDays($daysAgo)->format('Y-m-d-H-i-s').'.zip', 'backup');
        }

        $this->artisan('backup:clean --disable-notifications')->assertSuccessful();

        $this->assertSame(
            [now()->subDays(13)->format('Y-m-d-H-i-s').'.zip', now()->subDays(1)->format('Y-m-d-H-i-s').'.zip'],
            array_map('basename', $this->zips()),
        );
        $this->assertNull(config('backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than'));
    }

    /** A backup as the nightly job writes it: an AES-256 zip with the dump and the photos. */
    private function backupFile(string $password = 'test-archive-password'): void
    {
        File::ensureDirectoryExists("{$this->backups}/jude-boutique");
        $zip = new ZipArchive;
        $zip->open("{$this->backups}/jude-boutique/".now()->format('Y-m-d-H-i-s').'.zip', ZipArchive::CREATE);
        $zip->setPassword($password);
        foreach (['db-dumps/mysql-jude_test.sql' => "INSERT INTO settings VALUES ('restored');\n", 'public/products/restored.webp' => 'RIFF....WEBP'] as $name => $contents) {
            $zip->addFromString($name, $contents);
            $zip->setEncryptionName($name, ZipArchive::EM_AES_256);
        }
        $zip->close();
    }

    #[Test]
    public function req_saf_04_a_backup_restores_the_database_and_the_photos(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/added-after-the-backup.webp', 'new');
        Storage::disk('public')->put('rack/added-after-the-backup.webp', 'new');
        $this->backupFile();
        putenv('MIGRATE_DB_USERNAME=jude_migrate');
        putenv('MIGRATE_DB_PASSWORD=migrate-password');
        Process::fake();

        $this->artisan('jude:restore-backup --force')->assertSuccessful();

        // The dump goes into mysql with the migration account, whose password is never on the command line.
        Process::assertRan(function (PendingProcess $process) {
            $this->assertSame('mysql', basename($process->command[0])); // or the full path from DB_DUMP_BINARY_PATH
            $this->assertStringStartsWith('--defaults-file=', $process->command[1]);
            $this->assertSame('jude_test', $process->command[2]);
            $this->assertStringNotContainsString('migrate-password', implode(' ', $process->command));

            return true;
        });
        Storage::disk('public')->assertExists('products/restored.webp');
        Storage::disk('public')->assertMissing('products/added-after-the-backup.webp');
        Storage::disk('public')->assertMissing('rack/added-after-the-backup.webp');
        $this->assertSame([], File::glob(storage_path('app/restore-*')), 'The unpacked backup is removed');
    }

    #[Test]
    public function req_saf_04_with_the_wrong_password_nothing_is_touched(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/current.webp', 'current');
        $this->backupFile('another-password');
        putenv('MIGRATE_DB_USERNAME=jude_migrate');
        putenv('MIGRATE_DB_PASSWORD=migrate-password');
        Process::fake();

        $this->artisan('jude:restore-backup --force')
            ->expectsOutputToContain('Check BACKUP_ARCHIVE_PASSWORD')
            ->assertFailed();

        Process::assertNothingRan();
        Storage::disk('public')->assertExists('products/current.webp');
        $this->assertSame([], File::glob(storage_path('app/restore-*')));
    }

    #[Test]
    public function req_saf_04_a_backup_without_photos_leaves_the_photos_alone(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/current.webp', 'current');
        // As backup:run --only-db makes it: the database and nothing else.
        File::ensureDirectoryExists("{$this->backups}/jude-boutique");
        $zip = new ZipArchive;
        $zip->open("{$this->backups}/jude-boutique/".now()->format('Y-m-d-H-i-s').'.zip', ZipArchive::CREATE);
        $zip->setPassword('test-archive-password');
        $zip->addFromString('db-dumps/mysql-jude_test.sql', "SELECT 1;\n");
        $zip->setEncryptionName('db-dumps/mysql-jude_test.sql', ZipArchive::EM_AES_256);
        $zip->close();
        putenv('MIGRATE_DB_USERNAME=jude_migrate');
        putenv('MIGRATE_DB_PASSWORD=migrate-password');
        Process::fake();

        $this->artisan('jude:restore-backup --force')
            ->expectsOutputToContain('This backup holds no photos')
            ->assertSuccessful();

        Process::assertRanTimes(fn (PendingProcess $process) => $process->command[2] === 'jude_test', 1);
        Storage::disk('public')->assertExists('products/current.webp');
    }

    #[Test]
    public function req_saf_04_a_backup_storage_that_cannot_be_reached_is_reported(): void
    {
        config(['filesystems.disks.backups' => [
            'driver' => 'sftp', 'host' => '127.0.0.1', 'port' => 1, 'username' => 'backup',
            'privateKey' => base_path('tests/no-such-key'), 'timeout' => 2, 'throw' => true,
        ]]);
        putenv('MIGRATE_DB_USERNAME=jude_migrate');
        Process::fake();

        // Not "there are no backups", which would send whoever restores looking in the wrong place.
        $this->artisan('jude:restore-backup --list')
            ->expectsOutputToContain('The backup storage cannot be reached')
            ->doesntExpectOutputToContain('There are no backups')
            ->assertFailed();
        Process::assertNothingRan();
    }

    #[Test]
    public function req_saf_04_the_migration_password_can_be_typed_in_instead_of_set(): void
    {
        Storage::fake('public');
        $this->backupFile();
        Process::fake();

        $this->artisan('jude:restore-backup --force')
            ->expectsQuestion('Migration account (deploy/mysql/grants.sql)', 'jude_migrate')
            ->expectsQuestion('Password for jude_migrate', 'typed-password')
            ->assertSuccessful();

        Process::assertRan(fn (PendingProcess $process) => $process->command[2] === 'jude_test');
    }

    #[Test]
    public function req_saf_04_the_restore_asks_first_and_lists_the_backups(): void
    {
        $this->backupFile();
        putenv('MIGRATE_DB_USERNAME=jude_migrate');
        putenv('MIGRATE_DB_PASSWORD=migrate-password');
        Process::fake();

        // Asked in every environment, not only production; --force skips it.
        $this->artisan('jude:restore-backup')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed();
        Process::assertNothingRan();

        $this->artisan('jude:restore-backup --list')
            ->expectsOutputToContain(now()->format('Y-m-d-H-i-s').'.zip')
            ->assertSuccessful();
    }

    #[Test]
    public function req_db_16_the_backups_and_checks_run_on_their_own(): void
    {
        $this->assertSame('0 1 * * *', $this->scheduled('backup:clean')->expression);
        $this->assertSame('30 1 * * *', $this->scheduled('backup:run')->expression);
        $this->assertSame('0 9 * * *', $this->scheduled('backup:monitor')->expression);
        $this->assertSame('0 * * * *', $this->scheduled('jude:check-disk')->expression);

        // The server's crontab starts the scheduler every minute, as the web server's user.
        $this->assertStringContainsString('* * * * * cd /var/www/jude/current && php artisan schedule:run', file_get_contents(base_path('deploy/crontab')));
    }
}
