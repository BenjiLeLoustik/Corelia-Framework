<?php

/* ===== /Core/CLI/Commands/ModuleListCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;
use Corelia\CLI\ModuleManager;

class ModuleDisableCommand implements CommandInterface
{

    public function getName(): string { return 'module:disable'; }

    public function getDescription(): string { return 'Désactivation d\'un module.'; }

    public function execute( array $argv ): int
    {
        $manager = new ModuleManager( __DIR__ . '/../../../modules' );
        $manager->disable( $argv[2] ?? null );
        return 0;
    }

}