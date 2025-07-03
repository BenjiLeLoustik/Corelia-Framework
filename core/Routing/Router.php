<?php

/* ===== /Core/Routing/Router.php ===== */

namespace Corelia\Routing;

use Corelia\Http\Request;

/**
 * Routeur HTTP pour CoreliaPHP.
 */
class Router
{
    protected array $routes = [];

    /**
     * Ajoute une route.
     * @param string|array $methods HTTP method(s) (GET, POST, etc.)
     * @param string $path
     * @param array $handler [Classe, méthode]
     */
    public function add($methods, string $path, $handler): self
    {
        $path = '/' . trim($path, '/');
        foreach ((array)$methods as $method) {
            $this->routes[] = [
                'method'  => strtoupper($method),
                'path'    => $path,
                'handler' => $handler
            ];
        }
        return $this;
    }

    /**
     * Tente de trouver une route correspondant à la requête.
     */
    public function match(Request $request): ?Route
    {
        $reqMethod = strtoupper($request->method());
        $reqPath = '/' . trim(parse_url($request->uri(), PHP_URL_PATH), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $reqMethod) continue;

            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $reqPath, $matches)) {
                array_shift($matches);
                $routeObj = new Route($route['path'], $route['handler'][0], $route['handler'][1]);
                $routeObj->setParameters($matches);
                return $routeObj;
            }
        }
        return null;
    }

    /**
     * Retourne toutes les routes enregistrées sous forme de tableau.
     * @return array
     */
    public function getAll(): array
    {
        return $this->routes ?? [];
    }
}
