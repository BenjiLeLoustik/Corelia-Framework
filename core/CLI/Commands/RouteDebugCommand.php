<?php

/* ===== /Core/CLI/Commands/RouteDebugCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class RouteDebugCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'route:debug';
    }

    public function getDescription(): string
    {
        return 'Debug le matching d\'une URL.';
    }

    public function execute(array $argv): int
    {
        $base = __DIR__ . '/../../../';
        $path = $argv[2] ?? null;
        if (!$path) {
            echo "\033[33m Usage: php corelia route:debug <path> \033[0m \n";
            return 1;
        }

        $router     = require $base . 'src/routes.php';
        $request    = new \Corelia\Http\Request('GET', $path);
        $match      = $router->match($request);

        if ($match) {
            echo "\033[32m Route trouvée : \033[0m \n";
            echo "  \033[36m Contrôleur : \033[0m \033[34m {$match->getController()} \033[0m \n";
            echo "  \033[36m Méthode : \033[0m \033[33m{ $match->getMethod()} \033[0m \n";
            echo "  \033[36m Paramètres : \033[0m \033[35m " . json_encode($match->getParameters()) . " \033[0m \n";
        } else {
            echo "\033[31m Aucune route ne correspond à $path \033[0m \n";
        }
        return 0;
    }

}