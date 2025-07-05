<?php

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class ServeCommand implements CommandInterface
{

    public function getName(): string
    {
        return 'serve';
    }

    public function getDescription(): string
    {
        return 'Démarre le serveur de développement pour le framework ou un workspace donné.';
    }

    public function execute(array $argv): int
    {
        $workspace = $argv[2] ?? null;

        if (!$workspace) {
            // Serveur principal (framework)
            $port = 8000;
            $publicDir = dirname(__DIR__, 3) . '/public';
            $name = 'framework principal';
        } else {
            // Serveur d'un workspace
            $configFile = dirname(__DIR__, 3) . "/workspace/$workspace/config.json";
            if (!file_exists($configFile)) {
                echo "Le workspace '$workspace' n'existe pas ou n'a pas de config.json.\n";
                return 1;
            }
            $config = json_decode(file_get_contents($configFile), true);
            $port = $config['port'] ?? 8001;
            $publicDir = dirname(__DIR__, 3) . "/workspace/$workspace/public";
            $name = "workspace '$workspace'";
        }

        if (!is_dir($publicDir)) {
            echo "Le dossier public ($publicDir) est introuvable.\n";
            return 1;
        }

        echo "Démarrage de $name sur http://localhost:$port\n";
        passthru("php -S localhost:$port -t $publicDir");
        return 0;
    }

}