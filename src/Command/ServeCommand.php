<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class ServeCommand extends Command
{
    protected string $signature = 'serve {--host=127.0.0.1} {--port=8000}';
    protected string $description = 'Start the Switch local development web server';
    protected string $category = 'Server';

    public function handle(): int
    {
        $host = (string) $this->option('host', '127.0.0.1');
        $port = (string) $this->option('port', '8000');

        $basePath = getcwd() ?: '.';
        $publicDir = $basePath . '/public';

        if (!is_dir($publicDir)) {
            $this->warning("Directory 'public/' not found. Creating public/index.php...");
            mkdir($publicDir, 0777, true);
            file_put_contents($publicDir . '/index.php', '<?php echo "Switch Framework App Running!";');
        }

        $url = "http://{$host}:{$port}";

        $this->title("Switch Local Development Server");
        $this->details([
            'Server Address' => $url,
            'Document Root' => $publicDir,
            'Environment' => 'Development',
            'PHP Version' => PHP_VERSION,
        ]);

        $this->line();
        $this->info("Press Ctrl+C to stop the development server.");
        $this->line();

        passthru("php -S {$host}:{$port} -t public");

        return 0;
    }
}
