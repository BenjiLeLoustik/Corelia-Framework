<?php

/* ===== /modules/ORM/Commands/OrmEntitiesListCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;

class OrmEntitiesListCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'orm:entities:list';
    }

    public function getDescription(): string
    {
        return 'Liste toutes les entités générées du module ORM.';
    }

    public function execute(array $argv): int
    {
        $entityDir = __DIR__ . '/../Entity/Generated/';
        if (!is_dir($entityDir)) {
            echo "\033[31mDossier des entités générées introuvable.\033[0m\n\n";
            return 1;
        }

        $files = glob($entityDir . '*.php');
        if (empty($files)) {
            echo "\033[33mAucune entité générée trouvée.\033[0m\n\n";
            return 0;
        }

        echo "\n\033[1mEntités générées disponibles :\033[0m\n\n";
        foreach ($files as $file) {
            $entity = basename($file, '.php');
            echo "  \033[36m- $entity\033[0m\n";
        }
        echo "\n";
        return 0;
    }
}