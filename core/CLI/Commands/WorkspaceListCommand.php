<?php

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class WorkspaceListCommand implements CommandInterface
{
    
    public function getName(): string
    {
        return 'workspace:list';
    }

    public function getDescription(): string
    {
        return 'Liste tous les workspaces avec port, URL, date de création et état.';
    }

    public function execute(array $argv): int
    {
        $baseDir = dirname(__DIR__, 3) . '/workspace';
        if (!is_dir($baseDir)) {
            echo "Aucun dossier workspace trouvé.\n";
            return 1;
        }

        $dirs = array_filter(glob("$baseDir/*"), 'is_dir');
        if (empty($dirs)) {
            echo "Aucun workspace trouvé.\n";
            return 0;
        }

        echo str_pad("Nom", 18)
            . str_pad("Port", 8)
            . str_pad("URL", 28)
            . str_pad("Créé le", 22)
            . "État\n";
        echo str_repeat("-", 80) . "\n";

        foreach ($dirs as $dir) {
            $name = basename($dir);
            $configFile = "$dir/config.json";
            $port = "N/A";
            $url = "N/A";
            $created = date('Y-m-d H:i', filectime($dir));
            $etat = "Arrêté";

            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $port = $config['port'] ?? "N/A";
                $url = "http://localhost:$port/";

                // Test si le port est ouvert (serveur démarré)
                if ($port !== "N/A" && self::isPortOpen('127.0.0.1', $port)) {
                    $etat = "Démarré";
                }
            }

            echo str_pad($name, 18)
                . str_pad($port, 8)
                . str_pad($url, 28)
                . str_pad($created, 22)
                . $etat . "\n";
        }

        return 0;
    }

    // Fonction utilitaire pour tester si un port est ouvert
    private static function isPortOpen($host, $port, $timeout = 1)
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fwrite($fp, "GET / HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n");
            $response = fread($fp, 1024);
            fclose($fp);
            // Cherche le commentaire ou le header unique
            return strpos($response, 'Corelia Workspace') !== false
                || strpos($response, 'X-Corelia-Workspace') !== false;
        }
        return false;
    }

}