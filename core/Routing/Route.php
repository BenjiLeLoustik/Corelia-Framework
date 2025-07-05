<?php

/* ===== /Core/Routing/Route.php ===== */

namespace Corelia\Routing;

/**
 * Objet de routing pour une route matchée.
 *
 * Représente une route HTTP, son chemin, le contrôleur associé, la méthode à appeler
 * et les éventuels paramètres extraits de l'URL.
 * 
 * Cette classe sert d'objet de transfert entre le routeur et le système de dispatch.
 *
 * Usage typique :
 *   $route = new Route('/user/{id}', 'UserController', 'show');
 *   $route->setParameters(['id' => 42]);
 */
class Route
{
    /**
     * Chemin de la route (ex: /admin/dashboard)
     * @var string
     */
    protected string $path;

    /**
     * Nom du contrôleur associé à la route (ex: 'AdminController')
     * Peut être un FQCN ou un alias selon l'architecture du framework.
     * @var string
     */
    protected string $controller;

    /**
     * Méthode du contrôleur à appeler (par défaut 'index')
     * @var string
     */
    protected string $method;

    /**
     * Paramètres extraits de l'URL (ex: ['id' => 42])
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Constructeur.
     *
     * @param string $path        Chemin de la route (ex: '/admin/dashboard')
     * @param string $controller  Nom du contrôleur (ex: 'AdminController')
     * @param string $method      Méthode du contrôleur à appeler (défaut 'index')
     */
    public function __construct(string $path, string $controller, string $method = 'index')
    {
        $this->path = $path;
        $this->controller = $controller;
        $this->method = $method;
    }

    /**
     * Retourne le chemin de la route.
     *
     * @return string Chemin de la route (ex: '/admin/dashboard')
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retourne le nom du contrôleur associé à la route.
     *
     * @return string Nom du contrôleur
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Retourne la méthode du contrôleur à appeler.
     *
     * @return string Nom de la méthode (ex: 'index', 'show', etc.)
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retourne les paramètres extraits de l'URL pour cette route.
     *
     * @return array<string, mixed> Tableau associatif des paramètres (ex: ['id' => 42])
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Définit les paramètres extraits de l'URL pour cette route.
     *
     * @param array<string, mixed> $params Tableau associatif des paramètres
     * @return void
     */
    public function setParameters(array $params): void
    {
        $this->parameters = $params;
    }
}
