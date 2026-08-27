<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeSeederCommand extends Command
{
    protected string $signature = 'make:seeder {name}';
    protected string $description = 'Create a new database seeder class';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (empty($name)) {
            $name = $this->ask('What is the name of the Seeder class?');
        }

        if (empty($name)) {
            $this->error('The seeder name is required.');
            return 1;
        }

        $className = str_ends_with($name, 'Seeder') ? $name : $name . 'Seeder';
        $directory = getcwd() . '/database/seeders';

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $this->error("Seeder [{$className}] already exists!");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Switch\Database\Seeder\Seeder;

class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example:
        // \$this->call(UserSeeder::class);
    }
}

PHP;

        file_put_contents($filePath, $stub);
        $this->info("Seeder [{$className}] created successfully.");

        return 0;
    }
}
