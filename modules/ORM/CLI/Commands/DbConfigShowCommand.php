<?php

/* ===== /modules/ORM/CLI/Commands/DbConfigShowCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;

class DbConfigShowCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'db:config:show';
    }

    public function getDescription(): string
    {
        return 'Affiche la configuration de la base de données actuellement utilisée (.env).';
    }

    public function execute(array $argv): int
    {
        $vars = [
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD'
        ];

        echo "\n\033[1mConfiguration actuelle de la base de données :\033[0m\n\n";
        $maxLen = max(array_map('strlen', $vars));

        foreach ($vars as $var) {
            $val = getenv($var);

            if ($var === 'DB_PASSWORD' && $val !== false) {
                $val = str_repeat('*', max(strlen($val), 6));
            }

            if ($val === false || $val === '') {
                $val = "\033[31mNon défini\033[0m";
            }

            printf("  \033[36m%-{$maxLen}s\033[0m : %s\n", $var, $val);
        }
        echo "\n";
        return 0;
    }
}