<?php

declare(strict_types=1);

namespace Switch\Console\Command;

use Switch\Database\Connection\Connection;
use Switch\Database\Migration\Migrator;

class MigrateCommand extends Command
{
    protected string $signature = 'migrate {--rollback} {--refresh}';
    protected string $description = 'Run or rollback database migrations';
    protected string $category = 'Database';

    public function handle(): int
    {
        $basePath = getcwd() ?: '.';
        $migDir = $basePath . '/database/migrations';

        if (!is_dir($migDir)) {
            $this->warning("No migrations directory found at database/migrations.");
            return 0;
        }

        $dbFile = $basePath . '/database/database.sqlite';
        $dbDir = dirname($dbFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $connection = Connection::sqlite($dbFile);
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
}
