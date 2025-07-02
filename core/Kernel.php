<?php

/* ===== /Core/Kernel.php ===== */

namespace Corelia;

use Corelia\Event\EventDispatcher;
use Corelia\Module\ModuleManager;
use Corelia\Http\Request;
use Corelia\Http\Response;
use Corelia\Routing\Router;


class Kernel
{
    protected array $config = [];
    protected ModuleManager $moduleManager;
    protected EventDispatcher $eventDispatcher;

    public function __construct()
    {
        $this->loadEnv();

        // Initialise le gestionnaire de modules et le dispatcher d'évènements
        $this->moduleManager    = new ModuleManager( __DIR__ . '/../modules' );
        $this->eventDispatcher  = new EventDispatcher();
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
     * Point d'entrée principal : Traite la requête HTTP
     */
    public function handle(): void
    {
        
        $request    = new Request();
        $response   = new Response();
                
        // Création du routeur
        $router = new Router();

        // Enregistre dynamiquement toutes les routes de smodules activés (avec dépendances résolues)
        $this->moduleManager->registerModulesRoutes( $router );
        
        // Ajout éventuellement ici les routes "app" (hors modules) si besoins

        $match = $router->match( $request );
                    
        if ($match) {
            $controllerClass    = $match->getController();
            $method             = $match->getMethod();
            $params             = $match->getParameters();

            if ( class_exists( $controllerClass ) ) {
                $controller = new $controllerClass();

                // Injection du dispatcher si la méthode existe dans le contrôleur
                if( method_exists( $controller, 'setEventDispatcher' ) ){
                    $controller->setEventDispatcher($this->eventDispatcher);
                }

                if ( method_exists( $controller, $method ) ) {
                    ob_start();
                    call_user_func_array( [ $controller, $method ], $params );
                    $content = ob_get_clean();
                    $response->setStatusCode( 200 )->setContent( $content )->send();
                    return;
                }

            }
            $response->setStatusCode( 404 )->setContent( "Contrôleur ou méthode introuvable." )->send();
            return;
        }

        // Fallback : page d'accueil par défaut
        $response->setStatusCode( 200 )->setContent( $this->renderWelcomePage() )->send();
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

    /**
     * Permet d'accéder au gestionnaire d'événements global
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * Permet d'accéder au gestionnaire de modules
     */
    public function getModuleManager(): ModuleManager
    {
        return $this->moduleManager;
    }
}