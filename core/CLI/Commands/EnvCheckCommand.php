<?php

/* ===== /Core/CLI/Commands/EnvCheckCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class EnvCheckCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'env:check';
    }

    public function getDescription(): string
    {
        return 'Vérifie le .env et affiche les variables.';
    }

    public function execute( array $argv ): int
    {
        $base = __DIR__ . '/../../../';
        $envFile = $base . '.env';
        if (!file_exists($envFile)) {
            echo "\033[31m Le fichier .env est manquant. \033[0m \n";
            return 1;
        }

        echo "\n\033[32m Fichier .env trouvé. \033[0m \n Variables détectées : \n\n";
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if( strpos( trim( $line) , '#' ) === 0 ) continue;
            if( !strpos( $line, '=' ) ) continue;
            
            list( $key, $value ) = explode( '=', $line, 2 );

            $key    = trim( $key );
            $value  = trim( $value, " \t\n\r\0\x0B\"' ");

            echo "\033[37m $key = \033[0m\033[34m $value \033[0m \n";
        }
        return 0;
    }

}