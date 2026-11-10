<?php

namespace Tests\Feature;

use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/kasir-backup-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'kasir', 'database.connections.mysql.unix_socket' => '']);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function fakeDump(string $body): string
    {
        $bin = $this->dir.'/fake-dump.sh';
        file_put_contents($bin, "#!/bin/sh\n".$body);
        chmod($bin, 0755);

        return $bin;
    }

    public function test_creates_gzip_backup_and_keeps_only_latest_files(): void
    {
        $bin = $this->fakeDump("echo 'CREATE TABLE a (id int);'\necho '-- Dump completed on 2026-11-10'\n");
        foreach (['20200101-000000', '20200102-000000', '20200103-000000'] as $old) {
            touch("{$this->dir}/kasir-{$old}.sql.gz");
        }

        $this->artisan('db:backup', ['--dir' => $this->dir, '--bin' => $bin, '--keep' => 2])->assertExitCode(0);

        $files = array_values(array_filter(scandir($this->dir), fn ($f) => str_starts_with($f, 'kasir-')));
        $this->assertCount(2, $files);
        $new = collect($files)->last();
        $this->assertStringContainsString('CREATE TABLE a', gzdecode(file_get_contents("{$this->dir}/{$new}")));
        $this->assertFileDoesNotExist("{$this->dir}/kasir-20200101-000000.sql.gz");
    }

    public function test_incomplete_dump_fails_and_leaves_no_file(): void
    {
        $bin = $this->fakeDump("echo 'CREATE TABLE a (id int);'\n"); // tanpa baris "Dump completed"

        $this->artisan('db:backup', ['--dir' => $this->dir, '--bin' => $bin])->assertExitCode(1);

        $this->assertSame([], array_values(array_filter(scandir($this->dir), fn ($f) => str_starts_with($f, 'kasir-'))));
    }

    public function test_dump_program_error_fails(): void
    {
        $bin = $this->fakeDump("echo 'Access denied' >&2\nexit 2\n");

        $this->artisan('db:backup', ['--dir' => $this->dir, '--bin' => $bin])->assertExitCode(1);
    }
}
