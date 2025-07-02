<?php

/* ===== /Core/Routing/Route.php ===== */

namespace Corelia\Routing;

/**
 * Représente une route HTTP
 */
class Route
{

    protected string $path;
    protected string $controller;
    protected string $method;
    protected array $parameters;

    public function __construct( string $path, string $controller, string $method = 'index' )
    {
        $this->path         = $path;
        $this->controller   = $controller;
        $this->method       = $method;
        $this->parameters   = [];
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters( array $params ): void
    {
        $this->parameters = $params;
    }

}