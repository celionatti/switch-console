<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeFlowCommand extends Command
{
    protected string $signature = 'make:flow {name}';
    protected string $description = 'Create a new state machine flow class in app/Flows';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (empty($name)) {
            $name = $this->ask('What is the name of the Flow class?');
        }

        if (empty($name)) {
            $this->error('The flow name is required.');
            return 1;
        }

        $className = str_ends_with($name, 'Flow') ? $name : $name . 'Flow';
        $namespace = 'App\\Flows';

        if (str_contains($className, '/')) {
            $parts = explode('/', $className);
            $className = array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        $directory = getcwd() . '/app/Flows';
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $this->error("Flow [{$className}] already exists!");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Switch\Foundation\Flow\StateMachine;

class {$className}
{
    /**
     * Define the state machine lifecycle.
     */
    public static function create(string \$field = 'status'): StateMachine
    {
        return StateMachine::define(\$field)
            ->states(['draft', 'pending', 'active', 'archived'])
            ->initial('draft')
            ->allow('submit', 'draft', 'pending')
            ->allow('activate', ['draft', 'pending'], 'active', function (\$model, \$context) {
                // Guard: return true if allowed, false if rejected
                return true;
            })
            ->allow('archive', ['active', 'pending'], 'archived')
            ->allow('reopen', 'archived', 'draft');
    }
}

PHP;

        file_put_contents($filePath, $stub);
        $this->info("Flow [{$className}] created successfully.");

        return 0;
    }
}
