<?php

/* ===== /Core/Http/Request.php ===== */

namespace Corelia\Http;

/**
 * Classe représentant une requête HTTP.
 * Fournit des méthodes pour accéder aux données GET, POST, headers, JSON, etc.
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
     * Entêtes HTTP parsées (clés en minuscules)
     * @var array
     */
    protected array $headers;

    /**
     * Corps brut de la requête (utile pour JSON, PUT, etc.)
     * @var string|null
     */
    protected ?string $rawBody = null;

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
        $this->rawBody  = file_get_contents('php://input');
    }

    /**
     * Récupère une variable GET.
     * @param string $key       Nom de la variable
     * @param mixed  $default   Valeur par défaut si non trouvée
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Récupère une variable POST.
     * @param string $key       Nom de la variable
     * @param mixed  $default   Valeur par défaut si non trouvée
     * @return mixed
     */
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Récupère une variable serveur.
     * @param string $key       Nom de la variable serveur (ex: REQUEST_URI)
     * @param mixed  $default   Valeur par défaut si non trouvée
     * @return mixed
     */
    public function server(string $key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Récupère un header HTTP (insensible à la casse).
     * @param string $key       Nom du header (ex: 'Authorization')
     * @param mixed  $default   Valeur par défaut si non trouvé
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    /**
     * Parse les headers HTTP à partir de $_SERVER.
     * Garde les clés en minuscules pour faciliter l'accès.
     * Ajoute aussi Content-Type et Content-Length si présents.
     * @return array            Tableau associatif des headers HTTP
     */
    public function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            // Les headers HTTP sont dans $_SERVER sous la forme HTTP_HEADER_NAME
            if (strpos($key, 'HTTP_') === 0) {
                // Transforme HTTP_HEADER_NAME en Header-Name
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[strtolower($header)] = $value;
            }
        }
        // Ajoute Content-Type et Content-Length (non préfixés par HTTP_)
        foreach (['CONTENT_TYPE', 'CONTENT_LENGTH'] as $special) {
            if (isset($this->server[$special])) {
                $headers[strtolower(str_replace('_', '-', $special))] = $this->server[$special];
            }
        }
        return $headers;
    }

    /**
     * Récupère la méthode HTTP de la requête (GET, POST, PUT, etc.)
     * @return string
     */
    public function method(): string
    {
        return strtoupper($this->server('REQUEST_METHOD', 'GET'));
    }

    /**
     * Récupère l'URI demandée dans la requête.
     * @return string
     */
    public function uri(): string
    {
        return $this->server('REQUEST_URI', '/');
    }

    /**
     * Retourne le corps brut de la requête (utile pour JSON, PUT, etc.).
     * @return string|null
     */
    public function rawBody(): ?string
    {
        return $this->rawBody;
    }

    /**
     * Retourne le corps décodé en tableau si Content-Type = application/json.
     * @return array|null
     */
    public function json(): ?array
    {
        if (stripos($this->header('Content-Type', ''), 'application/json') !== false) {
            $data = json_decode($this->rawBody(), true);
            return is_array($data) ? $data : null;
        }
        return null;
    }

    /**
     * Récupère tous les paramètres GET.
     * @return array
     */
    public function allGet(): array
    {
        return $this->get;
    }

    /**
     * Récupère tous les paramètres POST.
     * @return array
     */
    public function allPost(): array
    {
        return $this->post;
    }

    /**
     * Récupère tous les headers HTTP.
     * @return array
     */
    public function allHeaders(): array
    {
        return $this->headers;
    }
}
