<?php

/* ===== /Core/CLI/Commands/ModuleDependenciesCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;
use Corelia\CLI\ModuleManager;

class ModuleDependenciesCommand implements CommandInterface
{

    public function getName(): string { return 'module:dependencies'; }

    public function getDescription(): string
    {
        return 'Affichage l\'arbre de dépendance d\'un module.';
    }

    public function execute(array $argv): int
    {
        $manager = new ModuleManager( __DIR__ . '/../../../modules' );
        $module = $argv[2] ?? null;

        if( !$module ){
            echo "\033[33m Usage: php corelia module:dependencies <module> \033[0m\n";
            return 1;
        }

        $deps = $manager->getDependenciesTree( $module );

        if( !$deps ){
            echo "\033[31m Module '$module' introuvable ou sans dépendances. \033[0m \n";
            return 1;
        }

        echo "\033[36m Dépendances pour $module :\033[0m \n";
        $printDeps = function( $deps, $level = 1 ) use (&$printDeps)
        {
            foreach( $deps as $dep => $subDeps ){
                echo str_repeat('  ', $level) . "\033[33m - $dep \033[0m\n";
                if (!empty($subDeps)) $printDeps($subDeps, $level + 1);
            }
        };

        $printDeps( $deps, 1 );
        return 0;
    }

}