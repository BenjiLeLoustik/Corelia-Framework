<?php

/* ===== /Core/CLI/Commands/CacheClearCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class CacheClearCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'cache:clear';
    }

    public function getDescription(): string
    {
        return 'Vide les caches du framework.';
    }

    public function execute(array $argv): int
    {
        $base = __DIR__ . '/../../../';
        $cacheDirs = [ $base . 'cache/routes', $base . 'cache/templates' ];
        foreach( $cacheDirs as $dir ){
            if( is_dir( $dir ) ){
                array_map( 'unlink', glob( "$dir/*" ) );
                echo "\033[32m Cache vidé : $dir \033[0m \n"; 
            }
        }

        echo "\033[32m Tous les caches ont été vidés. \033[0m \n";
        return 0;
    }

}