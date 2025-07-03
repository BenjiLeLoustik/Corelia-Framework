<?php

/* ===== /Core/CLI/Commands/MakeViewCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class MakeViewCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'make:view';
    }

    public function getDescription(): string
    {
        return 'Cré un template .ctpl';
    }

    public function execute(array $argv): int
    {
        $module = $argv[2] ?? null;
        $name   = $argv[3] ?? null;
        if( !$module || $name ){
            echo "\033[33m Usage: php corelia make:view <module> <name> \033[0m \n";
            return 1;
        }

        $base = __DIR__ . '/../../../';
        $viewDir = $module === 'App'
            ? $base . 'src/Views'
            : $base . "modules/$module/Views";
        
        if( !is_dir( $viewDir ) ) mkdir( $viewDir, 0777, true );
        $file = "$viewDir/$name.ctpl";

        if( file_exists( $file ) ){
            echo "\033[31m Le fichier $file existe déjà. \033[0m \n";
            return 1;
        }

        file_put_contents( $file, "{% block content %} \n <!-- $name template --> \n {% endblock %} \n" );
        echo "\033[32m Template créé : $file \033[0m \n";
        return 0;
    }   

}