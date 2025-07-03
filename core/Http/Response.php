<?php

/* ===== /Core/Http/Response.php ===== */

namespace Corelia\Http;

/**
 * Classe pour gérer une réponse HTTP.
 * Permet d'envoyer des headers, du contenu, gérer les codes HTTP, etc.
 */
class Response
{

    /**
     * Code HTTP de la réponse (ex: 200, 404, 500)
     * @var int
     */
    protected int $statusCode = 200;

    /**
     * Tableau associatif des headers HTTP à envoyer
     * @var array
     */
    protected array $headers = [];

    /**
     * Contenu de la réponse (HTML, JSON, etc.)
     * @var string
     */
    protected string $content = '';

    /**
     * Définit le code HTTP de la réponse.
     * @param int $code             Code HTTP (ex: 200, 404)
     * @return self
     */
    public function setStatusCode( int $code ): self
    {
        $this->statusCode = $code;
        http_response_code( $code );
        return $this;
    }

    /**
     * Ajoute un header HTTP à la réponse.
     * @param string $name          Nom du header (ex: Content-Type)
     * @param string $value         Valeur du header
     * @return self
     */
    public function addHeader( string $name, string $value ): self
    {
        $this->headers[ $name ] = $value;
        header("$name: $value");
        return $this;
    }

    /**
     * Définit le contenu de la réponse.
     * @param string $content       Contenu à envoyer (HTML, JSON, etc.)
     * @return self
     */
    public function setContent( string $content ): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Envoie la réponse HTTP complète (headers + contenu).
     * Affiche le contenu au client.
     */
    public function send(): void
    {
        // Les headers ont déjà été envoyés dans addHeader/setStatusCode
        echo $this->content;
    }

}