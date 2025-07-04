<?php

/* ===== /Core/Routing/Router.php ===== */

namespace Corelia\Routing;

use Corelia\Http\Request;

/**
 * Routeur HTTP pour CoreliaPHP.
 * Permet d'enregistrer des routes et de faire le matching avec une requête.
 */
class Router
{
    /**
     * Tableau des routes enregistrées.
     * @var array
     */
    protected array $routes = [];

    /**
     * Ajoute une route au routeur.
     *
     * @param string|array $methods  Méthode(s) HTTP (GET, POST, etc.)
     * @param string       $path     Chemin de la route (ex: /admin/{id})
     * @param array        $handler  Tableau [Classe, méthode] du contrôleur
     * @return self
     */
    public function add( $methods, string $path, $handler, ?string $name = null ): self
    {
        $path = '/' . trim($path, '/');
        foreach ((array)$methods as $method) {
            $this->routes[] = [
                'method'    => strtoupper($method),
                'path'      => $path,
                'handler'   => $handler,
                'name'      => $name
            ];
        }
        return $this;
    }
    

    /**
     * Recherche et retourne une route à partir de son nom.
     *
     * @param string $name   Nom unique de la route (défini dans l'attribut RouteAttribute)
     * @return Route|null    Objet Route correspondant ou null si aucune route ne correspond
     */
    public function getRouteByName( string $name ): ?Route
    {
        foreach( $this->routes as $route ){
            if( isset( $route['name'] ) && $route['name'] === $name ){
                return new Route( $route['path'], $route['handler'][0], $route['handler'][1] );
            }
        }
        return null;
    }

    /**
     * Recherche une route correspondant à la requête HTTP.
     *
     * @param Request $request
     * @return Route|null  Objet Route si trouvé, sinon null
     */
    public function match(Request $request): ?Route
    {
        $reqMethod = strtoupper($request->method());
        $reqPath = '/' . trim(parse_url($request->uri(), PHP_URL_PATH), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $reqMethod) continue;

            // Remplace {param} par une capture regex
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $reqPath, $matches)) {
                array_shift($matches); // Retire le match complet
                $routeObj = new Route($route['path'], $route['handler'][0], $route['handler'][1]);
                $routeObj->setParameters($matches);
                return $routeObj;
            }
        }
        return null;
    }

    /**
     * Retourne toutes les routes enregistrées.
     * @return array
     */
    public function getAll(): array
    {
        return $this->routes;
    }
}
