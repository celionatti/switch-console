<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $signature = 'make:controller {name} {--r|resource}';
    protected string $description = 'Create a new controller class';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if (empty($name)) {
            $this->error('Controller name is required.');
            return 1;
        }

        $className = ucfirst(str_replace('Controller', '', $name)) . 'Controller';
        $isResource = $this->hasOption('resource');

        $basePath = getcwd() ?: '.';
        $dir = $basePath . '/app/Controllers';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $className . '.php';
        if (file_exists($filePath)) {
            $this->warning("Controller {$className} already exists at app/Controllers/{$className}.php");
            return 1;
        }

        $methods = $isResource ? <<<PHP

    public function index()
    {
        return "{$className} index";
    }

    public function create()
    {
    }

    public function store()
    {
    }

    public function show(\$id)
    {
        return "Show {$className} #{\$id}";
    }

    public function edit(\$id)
    {
    }

    public function update(\$id)
    {
    }

    public function destroy(\$id)
    {
    }
PHP : <<<PHP

    public function index()
    {
        return "Hello from {$className}";
    }
PHP;

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

class {$className}
{{$methods}
}
PHP;

        file_put_contents($filePath, $content);

        $this->success("Controller [app/Controllers/{$className}.php] created successfully.");
        return 0;
    }
}
