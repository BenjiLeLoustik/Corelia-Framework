<?php

/* ===== /Core/Routing/Route.php ===== */

namespace Corelia\Routing;

/**
 * Objet de routing pour une route matchée.
 * Représente une route, son contrôleur, sa méthode et ses paramètres.
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
     * @var array
     */
    protected array $parameters = [];

    /**
     * Constructeur.
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
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retourne le nom du contrôleur.
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Retourne la méthode du contrôleur.
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retourne les paramètres de la route.
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Définit les paramètres de la route.
     * @param array $params
     */
    public function setParameters(array $params): void
    {
        $this->parameters = $params;
    }
}
