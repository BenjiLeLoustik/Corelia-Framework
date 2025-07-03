<?php

/* ===== /Core/CLI/Commands/RouteListCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class RouteListCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'route:list';
    }

    public function getDescription(): string
    {
        return 'Liste toutes les routes enregistrées.';
    }

    public function execute(array $argv): int
    {
        $base   = __DIR__ . '/../../../';
        $router = require $base . 'src/routes.php';
        $routes = $router->getAll();

        echo "\033[36m Liste des routes : \033[0m \n";

        foreach( $routes as $route ){
            echo "[\033[32m {$route['method']} 
                   \033[0m] \033[33m {$route['path']} 
                   \033[0m => \033[34m 
                   {$route['handler'][0]}::{$route['handler'][1]}
                   \033[0m\n";
        }

        return 0;
    }

}