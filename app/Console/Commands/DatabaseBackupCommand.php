<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup {output}';

    protected $description = 'Sao lưu MySQL an toàn trước khi chạy migration production';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('Lệnh backup hiện chỉ hỗ trợ MySQL.');

            return self::FAILURE;
        }

        $connection = config('database.connections.mysql');
        $output = $this->absoluteOutputPath((string) $this->argument('output'));
        File::ensureDirectoryExists(dirname($output));

        $process = new Process([
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--default-character-set=utf8mb4',
            '--host='.(string) $connection['host'],
            '--port='.(string) $connection['port'],
            '--user='.(string) $connection['username'],
            '--result-file='.$output,
            (string) $connection['database'],
        ], base_path(), [
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            File::delete($output);
            $this->error(trim($process->getErrorOutput()) ?: 'Không thể sao lưu database.');

            return self::FAILURE;
        }

        $this->info('Đã sao lưu database: '.$output);

        return self::SUCCESS;
    }

    private function absoluteOutputPath(string $path): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
