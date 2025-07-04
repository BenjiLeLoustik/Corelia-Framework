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
 * Gère l'initialisation, la configuration, le routing et l'exécution de la requête HTTP.
 * Version sans gestion de modules.
 */
class Kernel
{
    /**
     * Configuration chargée depuis .env
     * @var array
     */
    protected array $config = [];

    /**
     * Gestionnaire d'événements Corelia.
     * @var EventDispatcher
     */
    protected EventDispatcher $eventDispatcher;

    /**
     * Constructeur du Kernel.
     * Initialise le noyau, configure le reporting des erreurs, charge la configuration
     * d'environnement et instancie le gestionnaire d'événements.
     */
    public function __construct()
    {
        $this->setupErrorReporting();
        $this->loadEnv();
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * Configure le reporting des erreurs PHP pour le développement.
     * Affiche toutes les erreurs et les erreurs de démarrage.
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
     * Charge les variables d'environnement depuis le fichier .env.
     * Remplit $this->config et $_ENV pour l'application.
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
     * Traite la requête HTTP, résout la route, exécute le contrôleur ciblé,
     * gère le rendu (template, JSON, Response) et envoie la réponse au client.
     *
     * @return void
     */
    public function handle(): void
    {
        $request  = new Request();
        $response = new Response();
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

                    // Si ce n'est pas un objet Response, erreur explicite
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
