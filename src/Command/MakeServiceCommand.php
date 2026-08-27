<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeServiceCommand extends Command
{
    protected string $signature = 'make:service {name}';
    protected string $description = 'Create a new business domain service class in app/Services';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (empty($name)) {
            $name = $this->ask('What is the name of the Service class?');
        }

        if (empty($name)) {
            $this->error('The service name is required.');
            return 1;
        }

        $className = str_ends_with($name, 'Service') ? $name : $name . 'Service';
        $namespace = 'App\\Services';

        if (str_contains($className, '/')) {
            $parts = explode('/', $className);
            $className = array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        $directory = getcwd() . '/app/Services';
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $this->error("Service [{$className}] already exists!");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    /**
     * Execute domain business logic.
     *
     * @param array<string, mixed> \$data
     * @return mixed
     */
    public function handle(array \$data = []): mixed
    {
        // Domain business logic here
        return \$data;
    }
}

PHP;

        file_put_contents($filePath, $stub);
        $this->info("Service [{$className}] created successfully.");

        return 0;
    }
}
