<?php

namespace Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep test backups out of the real storage folder.
        $this->app->useStoragePath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'queue-backup-test-'.uniqid());
        $this->backupDirectory = storage_path('app/backups');

        config([
            'database.connections.mysql.database' => 'queue_system',
            'database.connections.mysql.password' => 'secret',
            'database.backup.mysqldump_path' => 'mysqldump',
            'database.backup.keep' => 3,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path());

        parent::tearDown();
    }

    public function test_it_runs_mysqldump_without_putting_the_password_on_the_command_line(): void
    {
        Process::fake();

        $this->artisan('db:backup')->assertSuccessful();

        Process::assertRan(function (PendingProcess $process) {
            $command = implode(' ', (array) $process->command);

            return str_starts_with($command, 'mysqldump ')
                && str_contains($command, '--single-transaction')
                && str_contains($command, 'queue_system')
                && ! str_contains($command, 'secret')
                && $process->environment['MYSQL_PWD'] === 'secret';
        });
    }

    public function test_it_keeps_only_the_most_recent_backups(): void
    {
        Process::fake();
        File::ensureDirectoryExists($this->backupDirectory);
        foreach (['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04', '2026-09-05'] as $day) {
            File::put("{$this->backupDirectory}/queue_system-{$day}_173000.sql", '-- old');
        }

        $this->artisan('db:backup')->assertSuccessful();

        $remaining = collect(File::files($this->backupDirectory))->map->getFilename()->sort()->values()->all();
        $this->assertSame([
            'queue_system-2026-09-03_173000.sql',
            'queue_system-2026-09-04_173000.sql',
            'queue_system-2026-09-05_173000.sql',
        ], $remaining);
    }

    public function test_it_reports_a_failed_backup(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'Access denied', exitCode: 2)]);

        $this->artisan('db:backup')
            ->expectsOutput('Backup failed: Access denied')
            ->assertFailed();
    }
}
