<?php

/* ===== /Core/CLI/Commands/WorkspaceListCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

/**
 * Commande CLI pour lister tous les workspaces Corelia.
 *
 * Affiche un tableau récapitulatif pour chaque workspace détecté dans le dossier /workspace :
 *   - Nom du workspace
 *   - Port attribué (issu de config.json)
 *   - URL d'accès local
 *   - Date de création du dossier
 *   - État (démarré ou arrêté)
 *
 * Usage typique :
 *   php corelia workspace:list
 *
 * Cette commande est utile pour avoir une vue d'ensemble rapide de tous les espaces de travail
 * disponibles sur l'installation Corelia, leur état et leur accessibilité.
 */
class WorkspaceListCommand implements CommandInterface
{
    /**
     * Retourne le nom unique de la commande CLI.
     *
     * Ce nom est utilisé pour appeler la commande via le terminal.
     * Exemple :
     *   php corelia workspace:list
     *
     * @return string Nom de la commande
     */
    public function getName(): string
    {
        return 'workspace:list';
    }

    /**
     * Retourne la description courte de la commande.
     *
     * Cette description apparaît dans la liste des commandes disponibles.
     *
     * @return string Description de la commande
     */
    public function getDescription(): string
    {
        return 'Liste tous les workspaces avec port, URL, date de création et état.';
    }

    /**
     * Retourne l'aide détaillée de la commande.
     *
     * Cette méthode affiche une documentation complète lors de l'appel avec --help ou -h.
     *
     * @return string Texte d'aide détaillé
     */
    public function getHelp(): string
    {
        return  <<<TXT
                Commande : workspace:list

                Description :
                    Liste tous les workspaces Corelia présents dans le dossier /workspace.
                    Pour chaque workspace, affiche :
                    - Le nom du workspace
                    - Le port attribué
                    - L'URL d'accès local
                    - La date de création
                    - L'état (démarré ou arrêté)

                Utilisation :
                    php corelia workspace:list

                Options :
                    --help, -h    Affiche cette aide

                Exemples :
                    php corelia workspace:list

                Notes :
                    - Un workspace est considéré comme "démarré" si son port répond et qu'il expose un header Corelia.
                    - Les workspaces sont détectés automatiquement dans le dossier /workspace à la racine du projet.
                TXT;
    }

    /**
     * Exécute la commande de listing des workspaces.
     *
     * Parcourt le dossier /workspace, détecte chaque workspace, lit sa configuration,
     * et affiche un tableau récapitulatif avec nom, port, URL, date de création et état.
     *
     * @param array $argv Arguments de la ligne de commande
     * @return int Code de sortie (0 = succès, 1 = erreur)
     */
    public function execute(array $argv): int
    {
        $baseDir = dirname(__DIR__, 3) . '/workspace';

        // Vérifie que le dossier workspace existe
        if (!is_dir($baseDir)) {
            echo "Aucun dossier workspace trouvé.\n";
            return 1;
        }

        // Récupère tous les sous-dossiers (workspaces)
        $dirs = array_filter(glob("$baseDir/*"), 'is_dir');
        if (empty($dirs)) {
            echo "Aucun workspace trouvé.\n";
            return 0;
        }

        // Affiche l'en-tête du tableau
        echo str_pad("Nom", 18)
            . str_pad("Port", 8)
            . str_pad("URL", 28)
            . str_pad("Créé le", 22)
            . "État\n";
        echo str_repeat("-", 80) . "\n";

        // Parcourt chaque workspace et affiche ses infos
        foreach ($dirs as $dir) {
            $name = basename($dir);
            $configFile = "$dir/config.json";
            $port = "N/A";
            $url = "N/A";
            $created = date('Y-m-d H:i', filectime($dir));
            $etat = "Arrêté";

            // Lecture de la configuration du workspace
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $port = $config['port'] ?? "N/A";
                $url = "http://localhost:$port/";

                // Vérifie si le port du workspace est ouvert (serveur démarré)
                if ($port !== "N/A" && self::isPortOpen('127.0.0.1', $port)) {
                    $etat = "Démarré";
                }
            }

            // Affiche la ligne pour ce workspace
            echo str_pad($name, 18)
                . str_pad($port, 8)
                . str_pad($url, 28)
                . str_pad($created, 22)
                . $etat . "\n";
        }

        return 0;
    }

    /**
     * Vérifie si un port TCP est ouvert sur l'hôte donné.
     *
     * Utilisé pour détecter si le serveur du workspace est en cours d'exécution.
     * Envoie une requête HTTP et cherche un header ou une signature Corelia.
     *
     * @param string $host Adresse IP ou nom d'hôte (ex: '127.0.0.1')
     * @param int $port Port TCP à tester
     * @param int $timeout Timeout en secondes (défaut : 1)
     * @return bool true si le port est ouvert et répond, false sinon
     */
    private static function isPortOpen($host, $port, $timeout = 1)
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            // Envoie une requête HTTP minimale
            fwrite($fp, "GET / HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n");
            $response = fread($fp, 1024);
            fclose($fp);
            // Cherche une signature Corelia dans la réponse
            return strpos($response, 'Corelia Workspace') !== false
                || strpos($response, 'X-Corelia-Workspace') !== false;
        }
        return false;
    }
}
