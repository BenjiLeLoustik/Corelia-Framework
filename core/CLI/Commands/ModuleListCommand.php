<?php

/* ===== /Core/CLI/Commands/ModuleListCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;
use Corelia\CLI\ModuleManager;

class ModuleListCommand implements CommandInterface
{

    public function getName(): string { return 'module:list'; }
    public function getDescription(): string { return 'Liste des modules.'; }

    public function execute( array $argv ): int
    {
        $manager = new ModuleManager( __DIR__ . '/../../../modules' );
        $manager->list();
        return 0;
    }

}