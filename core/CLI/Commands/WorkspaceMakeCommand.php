<?php

/* ===== /Core/CLI/Commands/WorkspaceMakeCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

/**
 * Commande CLI pour créer un nouveau workspace Corelia.
 *
 * Cette commande automatise la création de la structure complète d'un workspace :
 *  - Dossiers publics, config, contrôleurs, templates, var
 *  - Fichiers de config, .env, index.php, WelcomeController, template Welcome
 *  - Attribution automatique d'un port libre
 *  - Mise à jour de l'autoload Composer pour prise en compte du nouveau workspace
 *
 * Usage typique :
 *   php corelia workspace:make NomWorkspace
 */
class WorkspaceMakeCommand implements CommandInterface
{
    /**
     * Retourne le nom unique de la commande CLI.
     *
     * Ce nom est utilisé pour appeler la commande via le terminal.
     * Exemple :
     *   php corelia workspace:make NomWorkspace
     *
     * @return string Nom de la commande
     */
    public function getName(): string
    {
        return 'workspace:make';
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
        return 'Crée un nouveau workspace Corelia';
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
                Commande : workspace:make

                Description :
                    Crée un nouveau workspace Corelia avec toute la structure nécessaire (public, config, src, templates, etc.).
                    Attribue automatiquement un port libre et met à jour l'autoload Composer.

                Utilisation :
                    php corelia workspace:make <NomWorkspace>

                Options :
                    --help, -h    Affiche cette aide

                Exemples :
                    php corelia workspace:make Test

                Notes :
                    - Le nom du workspace doit être unique.
                    - Le script tools/update-workspace-autoload.php doit exister pour mettre à jour l'autoload.
                    - Un workspace créé sans autoload correct sera supprimé automatiquement.
                TXT;
    }

    /**
     * Exécute la commande de création d'un nouveau workspace.
     *
     * Crée la structure complète du workspace, génère les fichiers essentiels,
     * attribue un port libre, met à jour l'autoload Composer, et affiche les instructions de démarrage.
     *
     * @param array $argv Arguments de la ligne de commande
     * @return int Code de sortie (0 = succès, 1 = erreur)
     */
    public function execute(array $argv): int
    {
        $workspaceName = $argv[2] ?? null;
        if (!$workspaceName) {
            echo "Usage: php corelia workspace:make <NomWorkspace>\n";
            return 1;
        }

        $baseDir = dirname(__DIR__, 3) . '/workspace';
        $workspaceDir = "$baseDir/$workspaceName";

        // 1. Création des dossiers nécessaires
        if (is_dir($workspaceDir)) {
            echo "Le workspace '$workspaceName' existe déjà.\n";
            return 1;
        }

        mkdir($workspaceDir, 0777, true);
        mkdir("$workspaceDir/public", 0777, true);
        mkdir("$workspaceDir/config", 0777, true);
        mkdir("$workspaceDir/src/Controllers", 0777, true);
        mkdir("$workspaceDir/templates/Welcome", 0777, true);
        mkdir("$workspaceDir/var", 0777, true);

        // 2. Recherche des ports déjà utilisés pour éviter les conflits
        $usedPorts = [];
        foreach (glob("$baseDir/*/config.json") as $configFile) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['port'])) {
                $usedPorts[] = (int)$config['port'];
            }
        }
        $port = 8001;
        while (in_array($port, $usedPorts)) {
            $port++;
        }

        // 3. Génère le fichier de configuration du workspace
        $config = [
            "name" => $workspaceName,
            "port" => $port
        ];
        file_put_contents("$workspaceDir/config.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 4. Génère un fichier .env minimal
        file_put_contents("$workspaceDir/.env", "NAME=$workspaceName\nPORT=$port\n");

        // 5. Crée un index.php minimal dans le dossier public/
        $indexContent = <<<PHP
                        <?php
                        header('X-Corelia-Workspace: true');
                        require dirname(__DIR__, 3) . '/vendor/autoload.php';

                        ini_set('display_errors', 1);
                        ini_set('display_startup_errors', 1);
                        error_reporting(E_ALL);

                        \$workspaceName = basename(dirname(__DIR__)); // détecte automatiquement '$workspaceName'

                        use Corelia\Kernel;
                        \$kernel = new Kernel(\$workspaceName);
                        \$kernel->handle();
                        PHP;
        file_put_contents("$workspaceDir/public/index.php", $indexContent);

        // 6. Crée un contrôleur WelcomeController par défaut
        $controllerContent =    <<<PHP
                                <?php

                                namespace Workspace\\$workspaceName\\Controllers;

                                use Corelia\\Controller\\BaseController;
                                use Corelia\\Routing\\RouteAttribute;
                                use Corelia\\Http\\Response;

                                class WelcomeController extends BaseController
                                {
                                    #[RouteAttribute(path: '/', name: 'workspace.welcome', methods: ['GET'])]
                                    public function index(): Response
                                    {
                                        // Lecture de la config du workspace
                                        \$configPath = dirname(__DIR__, 3) . '/config.json';
                                        \$config = file_exists(\$configPath) ? json_decode(file_get_contents(\$configPath), true) : [];
                                        \$name = \$config['name'] ?? '$workspaceName';
                                        \$port = \$config['port'] ?? '$port';

                                        return \$this->render('Welcome/index.ctpl', [
                                            'name' => \$name,
                                            'port' => \$port
                                        ]);
                                    }
                                }
                                PHP;
        file_put_contents("$workspaceDir/src/Controllers/WelcomeController.php", $controllerContent);

        // 7. Crée un template Welcome par défaut
        $templateContent =  <<<HTML
                            <!DOCTYPE html>
                            <html lang="fr">
                                <head>
                                    <meta charset="UTF-8">
                                    <title>Workspace {{ name }} créé !</title>
                                    <style>
                                        /* ... (styles du template, voir code original) ... */
                                    </style>
                                </head>
                                <body>
                                    <div class="container">
                                        <span class="success-icon">🎉</span>
                                        <h1>Workspace <strong>{{ name }}</strong> créé avec succès !</h1>
                                        <p style="font-size:1.13rem; color:#444;">
                                            Votre espace de travail est prêt à l'emploi.<br>
                                            <a href="http://localhost:{{ port }}/" class="cta-btn" target="_blank">Accéder à {{ name }}</a>
                                        </p>
                                        <h2>Instructions pour démarrer :</h2>
                                        <ol>
                                            <li>
                                                <span style="font-size:1.1em;">🚀</span>
                                                <strong>Démarrer le serveur local :</strong><br>
                                                <code>php corelia serve {{ name }}</code>
                                            </li>
                                            <li>
                                                <span style="font-size:1.1em;">🌐</span>
                                                <strong>Accéder à votre workspace :</strong><br>
                                                <a href="http://localhost:{{ port }}/" target="_blank">http://localhost:{{ port }}/</a>
                                            </li>
                                            <li>
                                                <span style="font-size:1.1em;">🛠️</span>
                                                <strong>Modifier le code source :</strong><br>
                                                Éditez les fichiers dans <code>/workspace/{{ name }}/src/</code>, <code>/workspace/{{ name }}/templates/</code> et <code>/workspace/{{ name }}/public/</code>
                                            </li>
                                        </ol>
                                        <hr>
                                        <div class="footer">Framework Corelia &copy; 2025</div>
                                    </div>
                                </body>
                            </html>
                            HTML;
        file_put_contents("$workspaceDir/templates/Welcome/index.ctpl", $templateContent);

        echo "Workspace '$workspaceName' créé sur le port $port.\n";
        echo "Accès : http://localhost:$port/\n";

        // 8. Met à jour l'autoload Composer pour tous les workspaces
        $updateScript = dirname(__DIR__, 3) . '/tools/update-workspace-autoload.php';
        if (file_exists($updateScript)) {
            passthru("php " . escapeshellarg($updateScript));
            passthru("composer dump-autoload");
        } else {
            echo "Attention : script d'autoload non trouvé à $updateScript\n";
            // Suppression du workspace fraîchement créé pour éviter une incohérence
            $this->deleteDirectory($workspaceDir);
            echo "Le workspace '$workspaceName' a été supprimé pour éviter une incohérence.\n";
            return 1;
        }

        return 0;
    }

    /**
     * Suppression récursive d'un dossier et de son contenu.
     *
     * @param string $dir Dossier à supprimer
     * @return void
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return;
        if (!is_dir($dir) || is_link($dir)) {
            unlink($dir);
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item);
        }
        rmdir($dir);
    }
}
