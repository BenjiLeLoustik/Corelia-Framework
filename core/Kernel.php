<?php

/* ===== /Core/Kernel.php ===== */

namespace Corelia;

use Corelia\Http\Request;
use Corelia\Http\Response;

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
        
        $request    = new Request();
        $response   = new Response();
        
        $path   = parse_url( $request->uri(), PHP_URL_PATH );
        
        // Simple routing : /controller/method/params
        $segments       = array_filter( explode('/', $path) );
        $controllerName = !empty( $segments ) ? ucfirst( array_shift( $segments ) ) . 'Controller' : 'HomeController';
        $method         = !empty( $segments ) ? array_shift( $segments ) : 'index';
        $params         = $segments;

        // Recherche du contrôleur dans /src/Controllers
        $controllerClass = "App\\Controller\\$controllerName";

        if( !class_exists( $controllerClass ) ){
            $response->setStatusCode(200);
            $response->setContent( $this->renderWelcomePage() );
            $response->send();
            return;
        }

        $controller = new $controllerClass();

        if( !method_exists( $controller, $method ) ){
            $response->setStatusCode( 404 );
            $response->setContent("Méthode '$method' introuvable dans le contrôleur '$controllerName'.");
            $response->send();
            return;
        }

        // Appel de la méthode avec paramètres
        ob_start();
        call_user_func_array( [$controller, $method], $params );
        $content = ob_get_clean();
        
        $response->setStatusCode( 200 );
        $response->setContent( $content );
        $response->send();
    }

    /**
     * Affiche la page d'accueil par défaut quand aucun contrôleur n'est trouvé
     */
    protected function renderWelcomePage(): string
    {
        $welcomeView = __DIR__ . '/../../src/Views/welcome.ctpl';
        if( file_exists( $welcomeView ) ){
            return file_get_contents( $welcomeView );
        }else{
            return "<h1>Bienvenue sur CoreliaPHP</h1>
                    <p>Le framework est correctement installé.</p>
                    <p>Pour commencer, créez votre premier contrôleur dans :</p>
                    <pre><code>/src/Controller</code></pre>";
        }
    }
}