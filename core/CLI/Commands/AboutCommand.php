<?php

/* ===== /Core/CLI/Commands/AboutCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class AboutCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'about';
    }

    public function getDescription(): string
    {
        return 'Affichage les informations du framework';
    }

    public function execute(array $argv): int
    {
        echo "\n\033[36m CoreliaPHP \033[0m \n";
        echo "\033[36m Version : \033[0m 0.0.1-BETA \n";
        echo "\033[36m Auteur : \033[0m Boezio Benjamin \n";
        echo "\033[36m Documentation : \033[0m https://github.com/BenjiLeLoustik/Corelia-Framework/blob/main/HOW_TO_USE.md \n";
        echo "\033[36m Site officiel : \033[0m https://corelia.fr/ \n\n";
        return 0;
    }

}