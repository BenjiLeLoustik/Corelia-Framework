<?php

/* ===== /Core/CLI/Commands/ServeCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

/**
 * Commande CLI pour démarrer un serveur de développement PHP intégré.
 * 
 * Cette commande permet de lancer rapidement un serveur local pour :
 *   - le framework principal Corelia (par défaut, port 8000)
 *   - un workspace spécifique (port attribué dans la config du workspace)
 * 
 * Usage typique :
 *   php corelia serve
 *   php corelia serve NomWorkspace
 */
class ServeCommand implements CommandInterface
{
    /**
     * Retourne le nom unique de la commande CLI.
     * 
     * Ce nom est utilisé pour appeler la commande via le terminal.
     * Exemple :
     *   php corelia serve
     *   php corelia serve NomWorkspace
     *
     * @return string Nom de la commande
     */
    public function getName(): string
    {
        return 'serve';
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
        return 'Démarre le serveur de développement pour le framework ou un workspace donné.';
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
                Commande : serve

                Description :
                    Démarre le serveur de développement PHP intégré pour :
                    - Le framework principal Corelia (par défaut sur le port 8000)
                    - Un workspace spécifique (port défini dans la config du workspace)

                Utilisation :
                    php corelia serve
                    php corelia serve <NomWorkspace>

                Options :
                    --help, -h    Affiche cette aide

                Exemples :
                    # Démarrer le serveur pour le framework principal (public/)
                    php corelia serve

                    # Démarrer le serveur pour un workspace nommé "Test"
                    php corelia serve Test

                Notes :
                    - Le serveur utilise le serveur web intégré de PHP (php -S).
                    - Le port utilisé pour un workspace est défini dans workspace/<Nom>/config.json.
                    - Le dossier public/ doit exister dans le workspace ou à la racine du projet.
                TXT;
    }

    /**
     * Exécute la commande de démarrage du serveur de développement.
     * 
     * Si aucun workspace n'est précisé, démarre le serveur pour le framework principal (public/).
     * Sinon, démarre le serveur pour le workspace spécifié (workspace/<Nom>/public).
     * Affiche un message d'erreur si le workspace ou le dossier public n'existe pas.
     *
     * @param array $argv Arguments de la ligne de commande
     * @return int Code de sortie (0 = succès, 1 = erreur)
     */
    public function execute(array $argv): int
    {
        $workspace = $argv[2] ?? null;

        if (!$workspace) {
            // Démarrage du serveur pour le framework principal
            $port = 8000;
            $publicDir = dirname(__DIR__, 3) . '/public';
            $name = 'framework principal';
        } else {
            // Démarrage du serveur pour un workspace spécifique
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

        // Vérifie que le dossier public existe
        if (!is_dir($publicDir)) {
            echo "Le dossier public ($publicDir) est introuvable.\n";
            return 1;
        }

        // Affiche l'URL de démarrage et lance le serveur PHP intégré
        echo "Démarrage de $name sur http://localhost:$port\n";
        passthru("php -S localhost:$port -t $publicDir");
        return 0;
    }
}
