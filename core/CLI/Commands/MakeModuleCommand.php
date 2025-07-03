<?php

/* ===== /Core/CLI/Commands/MakeModuleCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;
use Corelia\CLI\Generator;

class MakeModuleCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'make:module';
    }

    public function getDescription(): string
    {
        return 'Crée un nouveau module.';
    }

    public function execute( array $argv ): int
    {
        ( new Generator( __DIR__ . '/../../../' ) )->makeModule( $argv[2] ?? null );
        return 0;
    }
}