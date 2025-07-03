<?php

/* ===== /Core/CLI/Commands/MakeControllerCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;
use Corelia\CLI\Generator;

class MakeControllerCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'make:controller';
    }

    public function getDescription(): string
    {
        return 'Crée un contrôleur.';
    }

    public function execute(array $argv): int
    {
        ( new Generator( __DIR__ . '/../../../' ) )->makeController( $argv[2], $argv[3] ?? null );
        return 0;
    }

}