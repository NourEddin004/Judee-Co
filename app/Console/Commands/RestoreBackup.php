<?php

namespace App\Console\Commands;

use App\Domain\Media\ImageStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Helpers\Format;
use ZipArchive;

/**
 * Puts the site back as it was at a nightly backup: the database and the uploaded images
 * (REQ-SAF-04). The database is loaded with the migration account, as deploys are (REQ-DB-34).
 * The steps around it are in deploy/RESTORE.md.
 */
#[Signature('jude:restore-backup {backup? : A file name from --list; the newest backup if left out} {--list : Show the backups} {--force : Restore without asking}')]
#[Description('Restore the database and the photos from a backup')]
class RestoreBackup extends Command
{
    use ConfirmableTrait;

    public function handle(): int
    {
        $destination = BackupDestination::create('backups', config('backup.backup.name'));

        // Checked first: a storage that cannot be reached would otherwise look like one with no backups.
        if (! $destination->isReachable()) {
            $this->error('The backup storage cannot be reached: '.$destination->connectionError()?->getMessage());
            $this->line('Check the BACKUP_SFTP_ settings, and run this as www-data, the user that owns the storage key.');

            return self::FAILURE;
        }

        $backups = $destination->backups();

        if ($this->option('list')) {
            $this->table(['Backup', 'Made (Jordan time)', 'Size'], $backups->map(fn (Backup $backup) => [
                basename($backup->path()), $this->made($backup), Format::humanReadableSize($backup->sizeInBytes()),
            ])->all());

            return self::SUCCESS;
        }

        $name = $this->argument('backup');
        $backup = $name ? $backups->first(fn (Backup $backup) => basename($backup->path()) === $name) : $backups->newest();

        if (! $backup) {
            $this->error($name ? "There is no backup called {$name}. Run with --list to see them." : 'There are no backups yet.');

            return self::FAILURE;
        }

        $account = $this->migrationAccount();

        if (! $account) {
            $this->error('Set MIGRATE_DB_USERNAME and MIGRATE_DB_PASSWORD (the migration account in deploy/mysql/grants.sql) first.');

            return self::FAILURE;
        }

        $this->warn('This replaces the whole database and every uploaded photo with the backup made '.$this->made($backup).'.');
        $this->warn('Orders placed since then are lost unless they are noted down first.');

        if (! $this->confirmToProceed('Restore this backup?', fn () => true)) {
            return self::FAILURE;
        }

        $started = microtime(true);
        $work = storage_path('app/restore-'.Str::random(8));
        File::ensureDirectoryExists($work, 0700);

        try {
            $this->line('Downloading '.basename($backup->path()).'...');
            $this->download($backup, "{$work}/backup.zip");
            $this->extract("{$work}/backup.zip", "{$work}/files");

            $this->line('Loading the database...');
            $this->loadDatabase("{$work}/files/db-dumps", $account, $work);

            $this->line('Putting the photos back...');
            $this->restoreImages("{$work}/files/public");

            $this->callSilently('cache:clear');
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            File::deleteDirectory($work);
        }

        $this->info(sprintf('Restored in %d seconds.', round(microtime(true) - $started)));

        return self::SUCCESS;
    }

    /**
     * The migration account from the environment, as deploys get it, or typed in, so its password
     * never has to go on a command line.
     *
     * @return array{user: string, password: string}|null
     */
    private function migrationAccount(): ?array
    {
        $user = getenv('MIGRATE_DB_USERNAME') ?: ($this->input->isInteractive() ? $this->ask('Migration account (deploy/mysql/grants.sql)', 'jude_migrate') : null);
        $password = getenv('MIGRATE_DB_PASSWORD');

        if ($user && $password === false && $this->input->isInteractive()) {
            $password = (string) $this->secret("Password for {$user}");
        }

        return $user ? ['user' => $user, 'password' => (string) $password] : null;
    }

    private function made(Backup $backup): string
    {
        return $backup->date()->timezone(config('app.timezone'))->format('j M Y, H:i');
    }

    private function download(Backup $backup, string $path): void
    {
        $source = $backup->stream();
        $target = fopen($path, 'w');
        stream_copy_to_stream($source, $target);
        fclose($target);
        fclose($source);
    }

    private function extract(string $zipPath, string $to): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('The backup file is damaged.');
        }

        $zip->setPassword((string) config('backup.backup.password'));

        if (! $zip->extractTo($to)) {
            throw new RuntimeException('The backup could not be opened ('.$zip->getStatusString().'). Check BACKUP_ARCHIVE_PASSWORD: it must be the one the backup was made with.');
        }

        $zip->close();
    }

    /** @param  array{user: string, password: string}  $account */
    private function loadDatabase(string $folder, array $account, string $work): void
    {
        $dump = File::glob("{$folder}/mysql-*.sql")[0] ?? throw new RuntimeException('The backup has no database dump.');
        $db = config('database.connections.'.config('database.default'));

        // The password goes in a file only this user can read, never on the command line. Only this
        // file is read (--defaults-file), so no other MySQL settings on the server can change the account.
        $options = "{$work}/client.cnf";
        $lines = ['[client]', 'user="'.addcslashes($account['user'], '\\"').'"', 'password="'.addcslashes($account['password'], '\\"').'"'];
        $lines[] = filled($db['unix_socket'] ?? null) ? "socket={$db['unix_socket']}" : "host={$db['host']}\nport={$db['port']}";
        File::put($options, implode("\n", $lines)."\n");
        chmod($options, 0600);

        $binaryFolder = rtrim((string) ($db['dump']['dump_binary_path'] ?? ''), '/');
        $input = fopen($dump, 'r');

        try {
            Process::timeout(60 * 60)->input($input)
                ->run([$binaryFolder ? "{$binaryFolder}/mysql" : 'mysql', "--defaults-file={$options}", $db['database']])
                ->throw();
        } finally {
            fclose($input);
        }
    }

    private function restoreImages(string $from): void
    {
        // A database-only backup (backup:run --only-db) leaves the photos as they are.
        if (! File::isDirectory($from)) {
            $this->warn('This backup holds no photos, so the photos were left as they are.');

            return;
        }

        $disk = Storage::disk('public');

        foreach (ImageStore::FOLDERS as $folder) {
            File::deleteDirectory($disk->path($folder));

            if (File::isDirectory("{$from}/{$folder}")) {
                File::copyDirectory("{$from}/{$folder}", $disk->path($folder));
            }
        }
    }
}
