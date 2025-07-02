<?php

/* ===== /Core/Kernel.php ===== */

namespace Corelia;

class Kernel
{
    protected array $config = [];
    protected array $modules = [];

    public function __construct()
    {
        $this->loadEnv();
        $this->loadModules();
    }

    /** 
     * Charge les variables d'environnements depuis le fichier .env 
     */
    protected function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        if( !file_exists( $envFile ) ){
            throw new \RuntimeException('.env file not found');
        }

        $lines = file( $envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        foreach( $lines as $line ){

            // Ignore les commentaires
            if( strpos( trim( $line ), '#' ) === 0 ) continue;

            [$key, $value] = array_map( 'trim', explode( '=', $line, 2 ) + [1=>null] );
            if( $key && $value !== null ){
                $this->config[$key] = $value;
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    /**
     * Charge les modules actifs en scannant les fichiers 'config.json' de chaques modules
     */
    protected function loadModules(): void
    {
        $modulesPath = __DIR__ . '/../../modules';
        if( !is_dir( $modulesPath ) ){
            return;
        }

        $dirs = scandir( $modulesPath );
        foreach( $dirs as $dir ){
            if( $dir === '.' || $dir === '..' ) continue;
            $configFile = $modulesPath . '/' . $dir . '/config.json';
            if( file_exists( $configFile ) ){
                $config = json_decode( file_get_contents( $configFile ), true );
                if( !empty( $config['enabled'] ) ){
                    $this->modules[$dir] = $config;
                }
            }
        }
    }

    /**
     * Point d'entrée principal : Traite la requête HTTP
     */
    public function handle(): void
    {
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = parse_url( $uri, PHP_URL_PATH );
        
        // Simple routing : /controller/method/params
        $segments       = array_filter( explode('/', $path) );
        $controllerName = !empty( $segments ) ? ucfirst( array_shift( $segments ) ) . 'Controller' : 'HomeController';
        $method         = !empty( $segments ) ? array_shift( $segments ) : 'index';
        $params         = $segments;

        // Recherche du contrôleur dans /src/Controllers
        $controllerClass = "App\\Controller\\$controllerName";

        if( !class_exists( $controllerClass ) ){
            // Aucun contrôleur trouvé : Afficher page d'accueil par défaut
            $this->renderWelcomePage();
            return;
        }

        $controller = new $controllerClass();

        if( !method_exists( $controller, $method ) ){
            header("HTTP/1.0 404 Nout Found");
            echo "Méthode '$method' introuvable dans le contrôleur '$controllerName'.";
            return;
        }

        // Appel de la méthode avec paramètres
        call_user_func_array( [ $controller, $method ], $params );
    }

    /**
     * Affiche la page d'accueil par défaut quand aucun contrôleur n'est trouvé
     */
    protected function renderWelcomePage(): void
    {
        $welcomeView = __DIR__ . '/../../src/Views/welcome.ctpl';
        if( file_exists( $welcomeView ) ){
            echo file_get_contents( $welcomeView );
        }else{
            echo "<h1>Bienvenue sur CoreliaPHP</h1>";
            echo "<p>Le framework est correctement installé.</p>";
            echo "<p>Pour commencer, créez votre premier contrôleur dans :</p>";
            echo "<pre><code>/src/Controller</code></pre>";
        }
    }
}