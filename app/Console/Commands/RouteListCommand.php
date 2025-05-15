<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RouteListCommand extends Command
{
    protected $signature = 'route:list';
    protected $description = 'Display all registered routes';

    public function handle()
    {
        $app = app();
        $router = $app->router;
        $routes = $router->getRoutes();

        $headers = ['Method', 'URI', 'Action'];
        $rows = [];

        foreach ($routes as $route) {
            $method = $route['method'];
            if (is_array($method)) {
                $method = implode('|', $method);
            }
            $rows[] = [
                $method,
                $route['uri'],
                $route['action']['uses'] ?? 'Closure'
            ];
        }

        $this->table($headers, $rows);
        return 0;
    }
}
