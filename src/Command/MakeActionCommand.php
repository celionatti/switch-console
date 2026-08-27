<?php

declare(strict_types=1);

namespace Switch\Console\Command;

use Switch\Console\Output\ConsoleOutput;

class MakeActionCommand extends Command
{
    protected string $signature = 'make:action {name}';
    protected string $description = 'Create a new domain action class';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (empty($name)) {
            $name = $this->ask('What is the name of the Action class?');
        }

        if (empty($name)) {
            $this->error('The action name is required.');
            return 1;
        }

        $className = str_ends_with($name, 'Action') ? $name : $name . 'Action';
        $namespace = 'App\\Actions';

        if (str_contains($className, '/')) {
            $parts = explode('/', $className);
            $className = array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        $directory = getcwd() . '/app/Actions';
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $this->error("Action [{$className}] already exists!");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Switch\Foundation\Action\Action;

class {$className} extends Action
{
    /**
     * Define validation rules for input parameters.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            // 'title' => 'required|min:3',
        ];
    }

    /**
     * Determine if the action is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Execute the domain action logic.
     */
    public function handle(array \$data): mixed
    {
        return \$data;
    }
}

PHP;

        file_put_contents($filePath, $stub);
        $this->info("Action [{$className}] created successfully.");

        return 0;
    }
}
