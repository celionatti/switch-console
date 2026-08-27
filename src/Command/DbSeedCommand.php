<?php

declare(strict_types=1);

namespace Switch\Console\Command;

use Switch\Database\Connection\Connection;
use Switch\Database\ORM\Model;
use Switch\Database\Seeder\SeederRunner;

class DbSeedCommand extends Command
{
    protected string $signature = 'db:seed {--class=}';
    protected string $description = 'Seed the database with records';
    protected string $category = 'Database';

    public function handle(): int
    {
        $class = (string) $this->option('class');
        if (empty($class)) {
            $class = 'Database\\Seeders\\DatabaseSeeder';
        }

        // Auto-require seeder files from database/seeders/ if needed
        $possibleDirs = [
            getcwd() . '/database/seeders',
            getcwd() . '/skeleton/database/seeders',
            defined('BASE_PATH') ? BASE_PATH . '/database/seeders' : null,
        ];

        foreach ($possibleDirs as $seederDir) {
            if ($seederDir && is_dir($seederDir)) {
                foreach (glob($seederDir . '/*.php') as $file) {
                    require_once $file;
                }
            }
        }

        // Support class name without namespace e.g. --class=UserSeeder
        if (!class_exists($class) && class_exists('Database\\Seeders\\' . $class)) {
            $class = 'Database\\Seeders\\' . $class;
        }

        if (!class_exists($class)) {
            $this->error("Seeder class [{$class}] does not exist.");
            return 1;
        }

        $this->info("Seeding database using [{$class}]...");

        $start = microtime(true);
        $connection = Model::getConnection();
        $runner = new SeederRunner($connection);

        try {
            $runner->run($class);
            $duration = round((microtime(true) - $start) * 1000, 2);
            $this->info("Database seeded successfully in {$duration}ms.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Seeding failed: " . $e->getMessage());
            return 1;
        }
    }
}
