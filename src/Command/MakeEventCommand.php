<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeEventCommand extends Command
{
    protected string $signature = 'make:event {name}';
    protected string $description = 'Create a new Event class';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if (empty($name)) {
            $this->error('Event name is required.');
            return 1;
        }

        $className = ucfirst(str_replace('Event', '', $name)) . 'Event';
        $basePath = getcwd() ?: '.';
        $dir = $basePath . '/app/Events';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $className . '.php';
        if (file_exists($filePath)) {
            $this->warning("Event {$className} already exists at app/Events/{$className}.php");
            return 1;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Events;

class {$className}
{
    public function __construct(
        public readonly mixed \$payload = null
    ) {
    }
}
PHP;

        file_put_contents($filePath, $content);
        $this->success("Event [app/Events/{$className}.php] created successfully.");
        return 0;
    }
}
