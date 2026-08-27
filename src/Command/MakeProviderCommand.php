<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeProviderCommand extends Command
{
    protected string $signature = 'make:provider {name}';
    protected string $description = 'Create a new service provider class';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (empty($name)) {
            $name = $this->ask('What is the name of the Service Provider class?');
        }

        if (empty($name)) {
            $this->error('The provider name is required.');
            return 1;
        }

        $className = str_ends_with($name, 'ServiceProvider') ? $name : $name . 'ServiceProvider';
        $directory = getcwd() . '/app/Providers';

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $this->error("Provider [{$className}] already exists!");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Providers;

use Switch\Container\Container;
use Switch\Container\ServiceProviderInterface;

class {$className} implements ServiceProviderInterface
{
    /**
     * Register services and interface bindings into the DI container.
     */
    public function register(Container \$container): void
    {
        // Example: \$container->singleton(MyServiceInterface::class, MyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Container \$container): void
    {
        //
    }
}

PHP;

        file_put_contents($filePath, $stub);
        $this->info("Provider [{$className}] created successfully.");

        return 0;
    }
}
