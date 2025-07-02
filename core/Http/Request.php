<?php

/* ===== /Core/Http/Request.php ===== */

namespace Corelia\Http;

/**
 * Classe représentant une requête HTTP.
 * Fournit des méthodes pour accéder aux données GET, POST, headers, etc.
 */
class Request
{

    protected array $get;
    protected array $post;
    protected array $server;
    protected array $headers;

    public function __construct()
    {
        $this->get      = $_GET ?? [];
        $this->post     = $_POST ?? [];
        $this->server   = $_SERVER ?? [];
        $this->headers  = $this->parseHeaders();
    }

    /**
     * Récupère une variable GET
     */
    public function get( string $key, $default = null )
    {
        return $this->get[ $key ] ?? $default;
    }

    /**
     * Récupère une variable POST
     */
    public function post( string $key, $default = null )
    {
        return $this->post[ $key ] ?? $default;
    }

    /**
     * Récupère une variable server (ex: REQUEST_URI)
     */
    public function server( string $key, $default = null )
    {
        return $this->server[ $key ] ?? $default;
    }

    /**
     * Récupère un header HTTP
     */
    public function header( string $key, $default = null )
    {
        $key = strtolower( $key );
        return $this->headers[ $key ] ?? $default;
    }

    /**
     * Parse les headers HTTP de $_SERVER
     */
    public function parseHeaders(): array
    {
        $headers = [];
        foreach( $this->server as $key => $value ){
            if( strpos( $key, 'HTTP_' ) === 0 ){
                $header = str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $key, 5 ) ) ) ) );
                $headers[strtolower( $header )] = $value;
            }
        }
        return $headers;
    }

    /**
     * Récupère la méthode HTTP (GET, POST, etc.)
     */
    public function method(): string
    {
        return strtoupper( $this->server('REQUEST_METHOD', 'GET') );
    }

    /**
     * Récupère l'URI demandée
     */
    public function uri(): string
    {
        return $this->server('REQUEST_URI', '/');
    }
}