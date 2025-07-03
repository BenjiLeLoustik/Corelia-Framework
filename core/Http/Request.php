<?php

/* ===== /Core/Http/Request.php ===== */

namespace Corelia\Http;

/**
 * Classe représentant une requête HTTP.
 * Fournit des méthodes pour accéder aux données GET, POST, headers, etc.
 */
class Request
{

    /**
     * Données GET ($_GET)
     * @var array
     */
    protected array $get;

    /**
     * Données POST ($_POST)
     * @var array
     */
    protected array $post;

    /**
     * Données serveur ($_SERVER)
     * @var array
     */
    protected array $server;

    /**
     * Entêtes HTTP parsées
     * @var array
     */
    protected array $headers;

    /**
     * Constructeur.
     * Initialise les propriétés à partir des superglobales PHP.
     */ 
    public function __construct()
    {
        $this->get      = $_GET ?? [];
        $this->post     = $_POST ?? [];
        $this->server   = $_SERVER ?? [];
        $this->headers  = $this->parseHeaders();
    }

    /**
     * Récupère une variable GET.
     * @param string $key               Nom de la variable
     * @param mixed  $default           Valeur par défaut si non trouvée
     * @return mixed
     */
    public function get( string $key, $default = null )
    {
        return $this->get[ $key ] ?? $default;
    }

    /**
     * Récupère une variable POST.
     * @param string $key               Nom de la variable
     * @param mixed  $default           Valeur par défaut si non trouvée
     * @return mixed
     */
    public function post( string $key, $default = null )
    {
        return $this->post[ $key ] ?? $default;
    }

    /**
     * Récupère une variable serveur.
     * @param string $key               Nom de la variable serveur (ex: REQUEST_URI)
     * @param mixed  $default           Valeur par défaut si non trouvée
     * @return mixed
     */
    public function server( string $key, $default = null )
    {
        return $this->server[ $key ] ?? $default;
    }

    /**
     * Récupère un header HTTP.
     * @param string $key               Nom du header (insensible à la casse)
     * @param mixed  $default           Valeur par défaut si non trouvé
     * @return mixed
     */
    public function header( string $key, $default = null )
    {
        $key = strtolower( $key );
        return $this->headers[ $key ] ?? $default;
    }

    /**
     * Parse les headers HTTP à partir de $_SERVER.
     * @return array                    Tableau associatif des headers HTTP
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
     * Récupère la méthode HTTP de la requête (GET, POST, etc.)
     * @return string
     */
    public function method(): string
    {
        return strtoupper( $this->server('REQUEST_METHOD', 'GET') );
    }

    /**
     * Récupère l'URI demandée dans la requête.
     * @return string
     */
    public function uri(): string
    {
        return $this->server('REQUEST_URI', '/');
    }
}