<?php

/* ===== /modules/Admin/CLI/Commands/AdminStatsCommand.php ===== */

use Corelia\CLI\CommandInterface;

class AdminStatsCommand implements CommandInterface
{

    public function getName(): string { return 'admin:stats'; }

    public function getDescription(): string { return 'Affichage des stats de l\'administration.'; }

    public function execute( array $argv ): int
    {
        echo "Nombre de requêtes : 42\n";
        return 0;
    }

}