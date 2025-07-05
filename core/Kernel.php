<?php

/* ===== /Core/Kernel.php ===== */

namespace Corelia;

use Corelia\Event\EventDispatcher;
use Corelia\Http\Request;
use Corelia\Http\Response;
use Corelia\Http\JsonResponse;
use Corelia\Routing\RouteAttribute;
use Corelia\Routing\Router;
use Corelia\Template\CoreliaTemplate;

/**
 * Noyau principal (Kernel) de Corelia.
 *
 * Gère l'initialisation, la configuration, le routing et l'exécution de la requête HTTP.
 * Version compatible multi-workspaces sans besoin de fichier de routes dans chaque workspace.
 * 
 * Usage typique :
 *   $kernel = new Kernel();
 *   $kernel->handle();
 */
class Kernel
{
    /**
     * Configuration chargée depuis .env
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Gestionnaire d'événements Corelia.
     * Permet d'enregistrer et de dispatcher des événements applicatifs.
     * @var EventDispatcher
     */
    protected EventDispatcher $eventDispatcher;

    /**
     * Nom du workspace actif (null si framework principal)
     * @var string|null
     */
    protected ?string $workspaceName = null;

    /**
     * Constructeur du Kernel.
     *
     * Initialise le workspace actif, la gestion des erreurs, charge la config .env
     * et instancie le gestionnaire d'événements.
     *
     * @param string|null $workspaceName Nom du workspace actif (ou null pour le framework principal)
     */
    public function __construct(?string $workspaceName = null)
    {
        $this->workspaceName = $workspaceName;
        $this->setupErrorReporting();
        $this->loadEnv();
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * Configure le reporting des erreurs PHP (dev friendly).
     *
     * @return void
     */
    protected function setupErrorReporting(): void
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    /**
     * Charge la configuration à partir du fichier .env à la racine du projet.
     * Remplit $this->config, $_ENV et les variables d'environnement système.
     *
     * @throws \RuntimeException si le fichier .env est absent
     * @return void
     */
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
     * Point d'entrée principal du framework.
     * 
     * - Si workspace actif, cherche automatiquement WelcomeController du workspace.
     * - Sinon, logique standard du framework principal avec routing auto par attributs.
     * - Gère les réponses HTTP, les erreurs et la page d'accueil.
     *
     * @return void
     */
    public function handle(): void
    {
        $request  = new Request();
        $response = new Response();

        // === Mode workspace : route automatique sur WelcomeController ===
        if ($this->workspaceName) {
            // On ne traite que la racine "/"
            if ($request->getPath() === '/') {
                $controllerClass = "Workspace\\{$this->workspaceName}\\Controllers\\WelcomeController";
                $controllerFile = __DIR__ . "/../../workspace/{$this->workspaceName}/src/Controllers/WelcomeController.php";

                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                }

                if (class_exists($controllerClass) && method_exists($controllerClass, 'index')) {
                    $controller = new $controllerClass();
                    // Injection éventuelle du gestionnaire d'événements
                    if (method_exists($controller, 'setEventDispatcher')) {
                        $controller->setEventDispatcher($this->eventDispatcher);
                    }
                    $result = $controller->index();
                    if ($result instanceof Response) {
                        $result->send();
                        return;
                    }
                }

                // Fallback : page d’erreur simple si le contrôleur n’existe pas
                $response->setStatusCode(404)->setContent(
                    "<h1>Workspace « {$this->workspaceName} » : page d'accueil non trouvée.</h1>"
                )->send();
                return;
            } else {
                // Autres routes : 404 simple (ou à complexifier selon besoins)
                $response->setStatusCode(404)->setContent(
                    "<h1>Page non trouvée dans le workspace « {$this->workspaceName} ».</h1>"
                )->send();
                return;
            }
        }

        // === Mode framework principal (standard) ===
        $router   = new Router();

        // Découverte automatique des routes via les attributs des contrôleurs (src/Controller)
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
                        [$className, $method->getName()],
                        $routeAttr->name
                    );
                }
            }
        }

        // Recherche de la route correspondante
        $match = $router->match($request);

        if ($match) {
            $controllerClass = $match->getController();
            $method          = $match->getMethod();
            $params          = $match->getParameters();

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();

                // Injection éventuelle du gestionnaire d'événements
                if (method_exists($controller, 'setEventDispatcher')) {
                    $controller->setEventDispatcher($this->eventDispatcher);
                }

                if (method_exists($controller, $method)) {
                    // Appel de la méthode du contrôleur
                    $result = call_user_func_array([$controller, $method], $params);

                    // Vérifie que la méthode retourne bien un objet Response (ou dérivé)
                    if ($result instanceof Response) {
                        $result->send();
                        return;
                    }

                    error_log("[Corelia] Erreur : le contrôleur {$controllerClass}::{$method} n'a pas retourné un objet Response.");

                    $response->setStatusCode(500)->setContent(
                        "Erreur : le contrôleur doit retourner un objet Response, JsonResponse ou RedirectResponse."
                    )->send();
                    return;
                } else {
                    $response->setStatusCode(404)->setContent(
                        "Méthode '{$method}' introuvable dans le contrôleur."
                    )->send();
                    return;
                }
            } else {
                $response->setStatusCode(404)->setContent(
                    "Contrôleur '{$controllerClass}' introuvable."
                )->send();
                return;
            }
        }

        // Si aucune route ne correspond, affiche la page d'accueil ou 404
        $response->setStatusCode(404)->setContent($this->renderWelcomePage())->send();
    }

    /**
     * Résout le chemin absolu d'un template selon la convention Corelia (sans modules).
     *
     * @param string $template  Nom du template (ex: 'Admin/dashboard.ctpl')
     * @return string           Chemin absolu du template
     */
    protected function resolveTemplatePath(string $template): string
    {
        // Recherche uniquement dans src/Views/
        return __DIR__ . "/../src/Views/{$template}";
    }

    /**
     * Rend la page d'accueil ou la page 404 par défaut si aucune route ne correspond.
     * Si le template notFound.ctpl existe, il est rendu, sinon un message HTML simple est affiché.
     *
     * @return string   HTML de bienvenue ou de page non trouvée
     */
    protected function renderWelcomePage(): string
    {
        $errorFileNotFound = __DIR__ . '/../src/Views/notFound.ctpl';
        if (file_exists($errorFileNotFound)) {
            $template = new \Corelia\Template\CoreliaTemplate($errorFileNotFound);
            return $template->render([]);
        } else {
            return "<h1>Bienvenue sur CoreliaPHP</h1>
                    <p>Le framework est correctement installé.</p>
                    <p>Pour commencer, créez votre premier contrôleur dans :</p>
                    <pre><code>/src/Controller</code></pre>";
        }
    }

    /**
     * Retourne l'EventDispatcher utilisé par le Kernel.
     *
     * @return EventDispatcher  Instance du gestionnaire d'événements
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }
}
