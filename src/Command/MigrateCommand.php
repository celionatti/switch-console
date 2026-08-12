<?php

declare(strict_types=1);

namespace Switch\Console\Command;

use Switch\Database\Connection\Connection;
use Switch\Database\Migration\Migrator;
use Switch\Database\ORM\Model;

class MigrateCommand extends Command
{
    protected string $signature = 'migrate {--rollback} {--refresh}';
    protected string $description = 'Run or rollback database migrations for MySQL, PostgreSQL, or SQLite';
    protected string $category = 'Database';

    public function handle(): int
    {
        $basePath = getcwd() ?: '.';
        $migDir = $basePath . '/database/migrations';

        if (!is_dir($migDir)) {
            $this->warning("No migrations directory found at database/migrations.");
            return 0;
        }

        $connection = $this->resolveConnection($basePath);
        $migrator = new Migrator($connection, $migDir);

        if ($this->hasOption('rollback')) {
            $this->title("Rolling back migrations...");
            $migrator->rollback();
            $this->success("Database migrations rolled back successfully.");
            return 0;
        }

        if ($this->hasOption('refresh')) {
            $this->title("Refreshing database migrations...");
            $migrator->rollback();
            $migrator->run();
            $this->success("Database migrations refreshed successfully.");
            return 0;
        }

        $this->title("Running database migrations...");
        $migrator->run();
        $this->success("Database migrations executed successfully.");

        return 0;
    }

    /**
     * Dynamically resolve the active database connection based on config / .env (MySQL, PostgreSQL, SQLite, etc.).
     */
    private function resolveConnection(string $basePath): Connection
    {
        // 1. Use existing active connection from Model if available
        if (class_exists(Model::class) && Model::hasConnection()) {
            return Model::getConnection();
        }

        // 2. Resolve from config/database.php and environment
        $configFile = $basePath . '/config/database.php';
        if (file_exists($configFile)) {
            $dbConfig = require $configFile;
            $defaultDriver = $dbConfig['default'] ?? (function_exists('env') ? env('DB_CONNECTION', 'sqlite') : 'sqlite');
            $connectionConfig = $dbConfig['connections'][$defaultDriver] ?? null;

            if ($connectionConfig !== null) {
                // Ensure directory and file exist for SQLite driver
                if ($defaultDriver === 'sqlite' && isset($connectionConfig['database'])) {
                    $dbPath = $connectionConfig['database'];
                    if ($dbPath !== ':memory:' && !str_contains($dbPath, '::memory::')) {
                        $dir = dirname($dbPath);
                        if (!is_dir($dir)) {
                            @mkdir($dir, 0777, true);
                        }
                        if (!file_exists($dbPath) && is_writable($dir)) {
                            @touch($dbPath);
                        }
                    }
                }

                return Connection::fromArray(array_merge(['driver' => $defaultDriver], $connectionConfig));
            }
        }

        // 3. Fallback to default SQLite database file
        $dbFile = $basePath . '/database/database.sqlite';
        $dbDir = dirname($dbFile);
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0777, true);
        }

        return Connection::sqlite($dbFile);
    }
}
