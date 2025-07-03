<?php

/* ===== /modules/ORM/Commands/DbDriversCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;

class DbDriversCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'db:drivers';
    }

    public function getDescription(): string
    {
        return 'Liste les drivers PDO disponibles pour la connexion à la base de données.';
    }

    public function execute(array $argv): int
    {
        $drivers = \PDO::getAvailableDrivers();

        if (empty($drivers)) {
            echo "\n\033[1;31mAucun driver PDO n'est disponible sur ce système.\033[0m\n\n";
            return 1;
        }

        echo "\n\033[1mDrivers PDO disponibles sur ce système :\033[0m\n\n";
        foreach ($drivers as $driver) {
            echo "  \033[36m- $driver\033[0m\n";
        }
        echo "\n";
        return 0;
    }
}