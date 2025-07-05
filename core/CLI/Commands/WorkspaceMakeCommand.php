<?php

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

class WorkspaceMakeCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'workspace:make';
    }

    public function getDescription(): string
    {
        return 'Crée un nouveau workspace Corelia';
    }

    public function execute(array $argv): int
    {
        $workspaceName = $argv[2] ?? null;
        if (!$workspaceName) {
            echo "Usage: php corelia workspace:make <NomWorkspace>\n";
            return 1;
        }

        $baseDir = dirname(__DIR__, 3) . '/workspace';
        $workspaceDir = "$baseDir/$workspaceName";

        // 1. Création des dossiers
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

        // 2. Recherche des ports déjà utilisés
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

        // 3. Génère le fichier de config
        $config = [
            "name" => $workspaceName,
            "port" => $port
        ];
        file_put_contents("$workspaceDir/config.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 4. Génère un .env
        file_put_contents("$workspaceDir/.env", "NAME=$workspaceName\nPORT=$port\n");

        // 5. Crée un index.php minimal dans public/
        $indexContent = <<<PHP
                        <?php
                        header('X-Corelia-Workspace: true');
                        require dirname(__DIR__, 3) . '/vendor/autoload.php';

                        ini_set('display_errors', 1);
                        ini_set('display_startup_errors', 1);
                        error_reporting(E_ALL);

                        \$workspaceName = basename(dirname(__DIR__)); // détecte automatiquement 'Test'

                        use Corelia\Kernel;
                        \$kernel = new Kernel(\$workspaceName);
                        \$kernel->handle();
                        PHP;
        file_put_contents("$workspaceDir/public/index.php", $indexContent);

        // 6. Crée un contrôleur Welcome
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

        // 7. Crée un template Welcome
        $templateContent =  <<<HTML
                            <!DOCTYPE html>
                            <html lang="fr">
                                <head>
                                    <meta charset="UTF-8">
                                    <title>Workspace {{ name }} créé !</title>
                                    <style>
                                        body {
                                            font-family: 'Segoe UI', 'Inter', Arial, sans-serif;
                                            background: linear-gradient(120deg, #f8f9fb 0%, #e0f7fa 100%);
                                            color: #222;
                                            margin: 0;
                                            padding: 0;
                                        }
                                        .container {
                                            background: #fff;
                                            border-radius: 16px;
                                            box-shadow: 0 4px 24px #00bfae22, 0 1.5px 8px #ddd;
                                            max-width: 540px;
                                            margin: 48px auto 0 auto;
                                            padding: 2.5em 2.2em 2em 2.2em;
                                            text-align: center;
                                        }
                                        h1 {
                                            color: #00bfae;
                                            font-size: 2.2rem;
                                            margin-bottom: 0.4em;
                                            font-weight: 800;
                                            letter-spacing: 1px;
                                        }
                                        h1 strong {
                                            color: #222;
                                        }
                                        h2 {
                                            margin-top: 2em;
                                            color: #3e3f44;
                                            font-size: 1.2rem;
                                            font-weight: 700;
                                        }
                                        .success-icon {
                                            font-size: 3rem;
                                            margin-bottom: 0.3em;
                                            display: block;
                                        }
                                        ol {
                                            text-align: left;
                                            margin: 1.3em auto 1.5em auto;
                                            padding-left: 1.1em;
                                            max-width: 420px;
                                        }
                                        li {
                                            margin-bottom: 1.2em;
                                            font-size: 1.08rem;
                                        }
                                        code, pre {
                                            background: #e0f7fa;
                                            color: #00bfae;
                                            padding: 2px 8px;
                                            border-radius: 6px;
                                            font-size: 1.01rem;
                                        }
                                        a {
                                            color: #00bfae;
                                            text-decoration: none;
                                            font-weight: 500;
                                            transition: color 0.15s;
                                        }
                                        a:hover {
                                            text-decoration: underline;
                                            color: #009e90;
                                        }
                                        .cta-btn {
                                            display: inline-block;
                                            background: #00bfae;
                                            color: #fff;
                                            font-weight: 700;
                                            font-size: 1.13rem;
                                            padding: 13px 36px;
                                            border-radius: 10px;
                                            margin-top: 1.2em;
                                            margin-bottom: 0.7em;
                                            text-decoration: none;
                                            box-shadow: 0 2px 12px #00bfae22;
                                            transition: background 0.18s, color 0.18s;
                                            border: none;
                                            cursor: pointer;
                                        }
                                        .cta-btn:hover {
                                            background: #00cfc0;
                                            color: #222;
                                        }
                                        hr {
                                            border: none;
                                            border-top: 1.5px solid #e0f7fa;
                                            margin: 2.1em 0 1.1em 0;
                                        }
                                        .footer {
                                            color: #aaa;
                                            font-size: 1rem;
                                        }
                                        @media (max-width: 600px) {
                                            .container {
                                                padding: 1.2em 0.7em 1.2em 0.7em;
                                            }
                                            ol { padding-left: 0.7em; }
                                        }
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
            // Suppression du workspace fraîchement créé
            $this->deleteDirectory($workspaceDir);
            echo "Le workspace '$workspaceName' a été supprimé pour éviter une incohérence.\n";
            return 1;
        }

        return 0;
    }

    /**
     * Suppression récursive d'un dossier
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
