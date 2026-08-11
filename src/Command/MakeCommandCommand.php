<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeCommandCommand extends Command
{
    protected string $signature = 'make:command {name}';
    protected string $description = 'Create a new custom Switch CLI command';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if (empty($name)) {
            $this->error('Command class name is required.');
            return 1;
        }

        $className = ucfirst(str_replace('Command', '', $name)) . 'Command';
        $cmdName = strtolower(preg_replace('/(?<!^)[A-Z]/', ':$0', str_replace('Command', '', $name)) ?? $name);

        $basePath = getcwd() ?: '.';
        $dir = $basePath . '/app/Commands';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $className . '.php';
        if (file_exists($filePath)) {
            $this->warning("Command {$className} already exists at app/Commands/{$className}.php");
            return 1;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Commands;

use Switch\Console\Command\Command;

class {$className} extends Command
{
    protected string \$signature = 'app:{$cmdName} {name?} {--flag}';
    protected string \$description = 'Custom CLI command description';
    protected string \$category = 'App';

    public function handle(): int
    {
        \$name = \$this->argument('name', 'World');
        
        \$this->title("Executing {$className}");
        \$this->info("Hello, {\$name}!");
        \$this->success("Command completed successfully.");

        return 0;
    }
}
PHP;

        file_put_contents($filePath, $content);
        $this->success("Command [app/Commands/{$className}.php] created successfully.");
        return 0;
    }
}
