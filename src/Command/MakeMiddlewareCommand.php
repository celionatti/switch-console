<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected string $signature = 'make:middleware {name}';
    protected string $description = 'Create a new PSR-15 middleware class';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if (empty($name)) {
            $this->error('Middleware name is required.');
            return 1;
        }

        $className = ucfirst(str_replace('Middleware', '', $name)) . 'Middleware';
        $basePath = getcwd() ?: '.';
        $dir = $basePath . '/app/Middleware';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $className . '.php';
        if (file_exists($filePath)) {
            $this->warning("Middleware {$className} already exists at app/Middleware/{$className}.php");
            return 1;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class {$className} implements MiddlewareInterface
{
    public function process(ServerRequestInterface \$request, RequestHandlerInterface \$handler): ResponseInterface
    {
        // Pre-processing...

        \$response = \$handler->handle(\$request);

        // Post-processing...

        return \$response;
    }
}
PHP;

        file_put_contents($filePath, $content);
        $this->success("Middleware [app/Middleware/{$className}.php] created successfully.");
        return 0;
    }
}
