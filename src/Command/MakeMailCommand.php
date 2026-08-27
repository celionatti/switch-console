<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeMailCommand extends Command
{
    protected string $signature = 'make:mail {name}';
    protected string $description = 'Create a new email mailable class';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (empty($name)) {
            $name = $this->ask('What is the name of the Mailable class?');
        }

        if (empty($name)) {
            $this->error('The mail name is required.');
            return 1;
        }

        $className = str_ends_with($name, 'Mail') ? $name : $name . 'Mail';
        $directory = getcwd() . '/app/Mail';

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $className . '.php';

        if (file_exists($filePath)) {
            $this->error("Mail [{$className}] already exists!");
            return 1;
        }

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Mail;

use Switch\Foundation\Mailer\Mailable;

class {$className} extends Mailable
{
    public function __construct(
        public array \$data = []
    ) {
    }

    /**
     * Build the message envelope and content.
     */
    public function build(): self
    {
        return \$this
            ->subject('Notification from ' . config('app.name', 'Switch'))
            ->html('<h1>Hello!</h1><p>You have a new message.</p>');
    }
}

PHP;

        file_put_contents($filePath, $stub);
        $this->info("Mail [{$className}] created successfully.");

        return 0;
    }
}
