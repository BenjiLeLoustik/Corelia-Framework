<?php

/* ===== /Core/Kernel.php ===== */

namespace Corelia;

use Corelia\Event\EventDispatcher;
use Corelia\Module\ModuleManager;
use Corelia\Http\Request;
use Corelia\Http\Response;
use Corelia\Routing\RouteAttribute;
use Corelia\Routing\Router;

class Kernel
{
    protected array $config = [];
    protected ModuleManager $moduleManager;
    protected EventDispatcher $eventDispatcher;

    public function __construct()
    {
        $this->setupErrorReporting();
        $this->loadEnv();
        $this->moduleManager    = new ModuleManager(__DIR__ . '/../modules');
        $this->eventDispatcher  = new EventDispatcher();
    }

    protected function setupErrorReporting(): void
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    protected function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            throw new \RuntimeException('.env file not found');
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            [$key, $value] = array_map('trim', explode('=', $line, 2) + [1 => null]);
            if ($key && $value !== null) {
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
        $router     = new Router();

        // 1. Routes des modules (attributs et config.json)
        $this->moduleManager->registerModulesRoutes($router);

        // 2. Routes des contrôleurs "app" (src/Controller)
        $controllersPath = __DIR__ . '/../src/Controller/';
        foreach (glob($controllersPath . '*Controller.php') as $file) {
            $className = "App\\Controller\\" . basename($file, '.php');
            if (!class_exists($className)) {
                require_once $file;
            }
            if (!class_exists($className)) continue;

            $rc = new \ReflectionClass($className);
            foreach ($rc->getMethods() as $method) {
                foreach ($method->getAttributes(RouteAttribute::class) as $attr) {
                    $routeAttr = $attr->newInstance();
                    $router->add(
                        $routeAttr->methods,
                        $routeAttr->path,
                        [$className, $method->getName()]
                    );
                }
            }
        }

        // 3. Matching et exécution
        $match = $router->match($request);

        if ($match) {
            $controllerClass = $match->getController();
            $method          = $match->getMethod();
            $params          = $match->getParameters();

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();

                if (method_exists($controller, 'setEventDispatcher')) {
                    $controller->setEventDispatcher($this->eventDispatcher);
                }

                if (method_exists($controller, $method)) {
                    $result = call_user_func_array([$controller, $method], $params);

                    if ($result instanceof \Corelia\Http\Response) {
                        $result->send();
                        return;
                    }
                    $response->setStatusCode(200)->setContent((string)$result)->send();
                    return;
                } else {
                    $response->setStatusCode(404)->setContent("Méthode '{$method}' introuvable dans le contrôleur.")->send();
                    return;
                }
            } else {
                $response->setStatusCode(404)->setContent("Contrôleur '{$controllerClass}' introuvable.")->send();
                return;
            }
        }

        // Fallback : page d'accueil par défaut
        $response->setStatusCode(200)->setContent($this->renderWelcomePage())->send();
    }

    protected function renderWelcomePage(): string
    {
        $welcomeView = __DIR__ . '/../../src/Views/welcome.ctpl';
        if (file_exists($welcomeView)) {
            return file_get_contents($welcomeView);
        } else {
            return "<h1>Bienvenue sur CoreliaPHP</h1>
                    <p>Le framework est correctement installé.</p>
                    <p>Pour commencer, créez votre premier contrôleur dans :</p>
                    <pre><code>/src/Controller</code></pre>";
        }
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    public function getModuleManager(): ModuleManager
    {
        return $this->moduleManager;
    }
}
