<?php

declare(strict_types=1);

namespace Switch\Console\Command;

use Switch\Router\Router;
use Switch\Router\RouteLoader;

class RouteListCommand extends Command
{
    protected string $signature = 'route:list';
    protected string $description = 'List all registered HTTP routes in a formatted table';
    protected string $category = 'HTTP & Routes';

    public function handle(): int
    {
        $router = new Router();
        $basePath = getcwd() ?: '.';

        $routesDir = $basePath . '/routes';
        if (is_dir($routesDir)) {
            $loader = new RouteLoader($router, $routesDir);
            $loader->load();
        }

        $routes = $router->getRoutes();

        if (empty($routes)) {
            $this->warning("No routes registered. Add routes in routes/web.php or routes/api.php.");
            return 0;
        }

        $this->title("Registered Application Routes");

        $headers = ['Method', 'URI Path', 'Name', 'Middleware'];
        $rows = [];

        foreach ($routes as $route) {
            $methods = implode('|', $route->getMethods());
            $path = $route->getPath();
            $name = $route->getName() ?? '-';
            $mw = !empty($route->getMiddleware()) ? implode(', ', $route->getMiddleware()) : '-';

            $rows[] = [$methods, $path, $name, $mw];
        }

        $this->table($headers, $rows);
        $this->info("Total registered routes: " . count($routes));

        return 0;
    }
}
