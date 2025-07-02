<?php

/* ===== /Core/Routing/Router.php ===== */

namespace Corelia\Routing;

use Corelia\Http\Request;

/**
 * Routeur HTTP pour CoreliaPHP.
 * Permet d'ajouter et de matcher des routes avec méthodes et paramètres.
 */
class Router
{

    protected array $routes = [];

    /**
     * Ajoute une route.
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path   Chemin avec ou sans paramètres (ex: /blog/{id})
     * @param array  $handler [Classe, méthode]
     */
    public function add( string $method, string $path, array $handler ): self
    {
        // Normalisation du chemin
        $path = '/' . trim( $path, '/' );
        $this->routes[] = [
            'method'    => $method,
            'path'      => $path,
            'handler'   => $handler
        ];
        return $this;
    }

    /**
     * Tente de trouver une route correspondant à la requête.
     */
    public function match( Request $request ): ?Route
    {
        $reqMethod = strtoupper($request->method());
        $reqPath = '/' . trim(parse_url($request->uri(), PHP_URL_PATH), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $reqMethod) continue;

            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $reqPath, $matches)) {
                array_shift($matches); // Retire la chaîne complète

                // Crée une instance de Route avec les bons paramètres
                $routeObj = new Route($route['path'], $route['handler'][0], $route['handler'][1]);
                $routeObj->setParameters($matches);
                return $routeObj;
            }
        }
        return null;
    }

}