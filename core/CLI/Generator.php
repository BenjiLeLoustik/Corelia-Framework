<?php

/* ===== /core/CLI/Generator.php ===== */

namespace Corelia\CLI;

/**
 * Classe utilitaire pour générer des modules et des contrôleurs dans l'application Corelia.
 */
class Generator
{
    /**
     * Chemin de base du projet (racine).
     * @var string
     */
    protected string $basePath;

    /**
     * Constructeur.
     * @param string $basePath              Chemin racine du projet
     */
    public function __construct(string $basePath) { $this->basePath = rtrim($basePath, '/\\'); }

    /**
     * Génère la structure d'un nouveau module avec son contrôleur et sa vue de base.
     * @param string|null $name             Nom du module à créer
     */
    public function makeModule(?string $name)
    {
        if( !$name ){
            echo "\033[33m Nom du module requis. \033[0m \n";
            return;
        }

        $modulePath = "{$this->basePath}/modules/$name";
        if ( is_dir( $modulePath ) ) {
            echo "\033[31m Le module $name existe déjà. \033[0m \n";
            return;
        }

        mkdir( "$modulePath/Views", 0777, true );
        mkdir( "$modulePath/Commands", 0777, true );

        // config.json
        $config = [
            "name" => $name,
            "description" => "Module $name généré automatiquement",
            "enabled" => true,
            "autoload" => [
                "psr-4" => [
                    "Modules\\$name\\" => "modules/$name/"
                ]
            ],
            "routes" => []
        ];

        file_put_contents( "$modulePath/config.json", json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

        // Contrôleur de base (évite le doublon)
        $this->makeController( $name, "{$name}Controller" );

        // Vue de base
        file_put_contents( "$modulePath/Views/index.ctpl", "<h1>Bienvenue dans le module $name</h1> \n" );

        echo "\033[32m Module $name généré avec succès. \033[0m \n";
    }

    /**
     * Génère un contrôleur soit dans l'app principale, soit dans un module existant.
     * @param string|null $moduleOrName     Nom du module ou du contrôleur (selon usage)
     * @param string|null $controllerName   Nom du contrôleur (optionnel, pour module)
     */
    public function makeController(?string $moduleOrName, ?string $controllerName = null)
    {
        // Si $controllerName est null, on crée dans l'app principale
        if( !$controllerName ) {

            $name = $moduleOrName;

            if ( !$name ) {
                echo "\033[33m Nom du contrôleur requis. \033[0m \n";
                return;
            }

            $ctrlPath = "{$this->basePath}/src/Controller/{$name}Controller.php";

            if( file_exists( $ctrlPath ) ) {
                echo "\033[31m Le contrôleur $name existe déjà. \033[0m \n";
                return;
            }

            $namePath = strtolower( $name );

            $code = <<<PHP
                    <?php

                    /* ===== /src/Controller/{$name}Controller.php ===== */
                    namespace App\Controller;

                    use Corelia\Controller\BaseController;
                    use Corelia\Routing\RouteAttribute;
                    use Corelia\Http\Response;

                    /**
                     * Contrôleur {$name}Controller
                     */
                    class {$name}Controller extends BaseController
                    {
                        /**
                         * Affichage de la page index de $name
                         * 
                         * @return array 
                         */
                        #[RouteAttribute(path: '/{$namePath}', template: '{$name}/index.ctpl')]
                        public function index(): array
                        {
                            return [ "welcomeController" => "Votre contrôleur `{$name}Controller` a bien été créé !" ];
                        }
                    }

                    PHP;

            file_put_contents( $ctrlPath, $code );

            echo "\033[32m Contrôleur $name généré dans src/Controller. \033[0m \n";

        }else{

            // Cas module + contrôleur
            $module     = $moduleOrName;
            $name       = $controllerName;
            $moduleDir  = "{$this->basePath}/modules/$module";
            $ctrlPath   = "$moduleDir/{$name}.php";

            if( !is_dir( $moduleDir ) ) {
                echo "\033[31m Module $module introuvable.\033[0m \n";
                return;
            }

            if( file_exists( $ctrlPath ) ) {
                echo "\033[31m Le contrôleur $name existe déjà dans $module. \033[0m \n";
                return;
            }

            $modulePath = strtolower( $module );

            $code = <<<PHP
                    <?php

                    /* ===== /modules/{$module}/{$name}.php ===== */

                    namespace Modules\\{$module};

                    use Corelia\\Controller\\BaseController;
                    use Corelia\\Routing\\RouteAttribute;
                    use Corelia\\Http\\Response;

                    /** 
                     * Contrôleur principal du module {$module}.
                     * Hérite de BaseController pour profiter des fonctionnalités communes aux contrôleurs.
                     * 
                     * @package Modules\\{$module}
                     */
                    class {$name} extends BaseController
                    {
                        /**
                         * Affichage de la page $module de $module
                         * 
                         * @return array 
                         */
                        #[RouteAttribute(path: '/{$modulePath}', template: '{$module}::index.ctpl')]
                        public function index(): array
                        {
                            return [ "welcomeController" => "Votre module `{$module}` a bien été créé !" ];
                        }
                    }

                    PHP;

            file_put_contents( $ctrlPath, $code );

            echo "\033[32m Contrôleur $name généré dans le module $module. \033[0m \n";
        }
    }
}
