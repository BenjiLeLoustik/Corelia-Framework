<?php

/* ===== /Core/Routing/Router.php ===== */

namespace Corelia\Routing;

use Corelia\Http\Request;

/**
 * Gestionnaire des routes
 */
class Router
{

    protected array $routes = [];

    /**
     * Ajoute une route
     */
    public function add( string $path, string $controller, string $method = 'index' ): self
    {
        $this->routes[ $path ] = new Route( $path, $controller, $method );
        return $this;
    }

    public function match( Request $request ): ?Route
    {
        $uri = parse_url( $request->uri(), PHP_URL_PATH );
        $uri = rtrim( $uri, '/' );

        // Recherche simple (exacte)
        if( isset( $this->routes[ $uri ] ) ){
            return $this->routes[ $uri ];
        }

        // Recherche avec paramètres (ex: /blog/show/123)
        foreach( $this->routes as $route ){
            $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route->getPath() );
            $pattern = '#^' . $pattern . '$#';

            if( preg_match( $pattern, $uri, $matches ) ) {
                array_shift( $matches );
                $route->setParameters( $matches );
                return $route;
            }
        }

        return null;
    }

}