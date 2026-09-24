<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

#[Signature('db:backup')]
#[Description('Save a copy of the MySQL database to storage/app/backups, keeping the most recent ones')]
class BackupDatabase extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $database = config('database.connections.mysql');
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $file = $directory.DIRECTORY_SEPARATOR."{$database['database']}-".now()->format('Y-m-d_His').'.sql';

        // The password goes through MYSQL_PWD so it never appears in the process list.
        $result = Process::env(['MYSQL_PWD' => (string) $database['password']])
            ->timeout(300)
            ->run([
                config('database.backup.mysqldump_path'),
                "--host={$database['host']}",
                "--port={$database['port']}",
                "--user={$database['username']}",
                '--single-transaction',
                "--result-file={$file}",
                $database['database'],
            ]);

        if ($result->failed()) {
            File::delete($file);
            $this->error('Backup failed: '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        // Newest first (the timestamp in the name sorts by date); delete everything past the limit.
        collect(File::glob($directory.DIRECTORY_SEPARATOR."{$database['database']}-*.sql"))
            ->sortDesc()
            ->slice(config('database.backup.keep'))
            ->each(fn (string $old) => File::delete($old));

        $this->info("Backup saved to {$file}");

        return self::SUCCESS;
    }
}
