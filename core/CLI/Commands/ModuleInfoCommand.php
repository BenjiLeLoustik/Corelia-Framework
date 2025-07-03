<?php

/* ===== /Core/CLI/Commands/ModuleListCommand.php ===== */

use Corelia\CLI\CommandInterface;
use Corelia\CLI\ModuleManager;

class ModuleInfoCommand implements CommandInterface
{

    public function getName(): string { return 'module:info'; }

    public function getDescription(): string {
        return 'Affichage les informations détaillées d\'un module.';
    }

    public function execute( array $argv ): int
    {
        $manager = new ModuleManager( __DIR__ . '/../../../modules' );
        $module = $argv[2] ?? null;
        if( !$module ){
            echo "\033[33mUsage: php corelia module:info <module>\033[0m\n";
            return 1;
        }

        $info = $manager->getInfo( $module );
        if( !$info ){
            echo "\033[31mModule '$module' introuvable.\033[0m\n";
            return 1;
        }

        echo "\033[36m Module: \033[0m {$info['name']} \n";
        echo "\033[36m Etat: \033[0m   " . ($info['enabled'] ? "\033[32m Activé \033[0m" : "\033[31m Désactivé \033[0m") . "\n";
        echo "\033[36m Version: \033[0m {$info['version']} \n";
        echo "\033[36m Description: \033[0m {$info['description']} \n";
        echo "\033[36m Dépendances: \033[0m " . (empty($info['dependencies']) ? "\033[33m Aucune \033[0m" : implode(', ', $info['dependencies'])) . "\n";
        echo "\033[36m Routes: \033[0m \n";

        foreach( $info['routes'] ?? [] as $route ){
            echo "  [\033[32m {$route['method']} \033[0m] \033[33m {$route['path']} \033[0m => \033[34m {$route['handler'][0]}::{$route['handler'][1]} \033[0m \n";
        }

        return 0;
    }
}