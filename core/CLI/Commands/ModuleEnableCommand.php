<?php

/* ===== /Core/CLI/Commands/ModuleListCommand.php ===== */

use Corelia\CLI\CommandInterface;
use Corelia\CLI\ModuleManager;

class ModuleEnableCommand implements CommandInterface
{

    public function getName(): string { return 'module:enable'; }

    public function getDescription(): string { return 'Activation d\'un module.'; }

    public function execute( array $argv ): int
    {
        $manager = new ModuleManager( __DIR__ . '/../../../modules' );
        $manager->enable( $argv[2] ?? null );
        return 0;
    }

}