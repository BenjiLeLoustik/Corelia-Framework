<?php

/* ===== /modules/ORM/Commands/MakeEntityCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;

class MakeEntityCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'make:entity';
    }

    public function getDescription(): string
    {
        return 'Génère une entité, son répository et une migration.';
    }

    public function execute(array $argv): int
    {
        // Logique de génération ici
        return 0;
    }

}