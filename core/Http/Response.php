<?php

/* ===== /Core/Http/Response.php ===== */

namespace Corelia\Http;

/**
 * Classe pour gérer une réponse HTTP.
 * Permet d'envoyer des headers, du contenu, gérer les codes HTTP, etc.
 */
class Response
{

    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';

    /**
     * Définit le code HTTP de la réponse
     */
    public function setStatusCode( int $code ): self
    {
        $this->statusCode = $code;
        http_response_code( $code );
        return $this;
    }

    /**
     * Ajoute un header HTTP
     */
    public function addHeader( string $name, string $value ): self
    {
        $this->headers[ $name ] = $value;
        header("$name: $value");
        return $this;
    }

    /**
     * Définit le contenu de la réponse
     */
    public function setContent( string $content ): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Envoie la réponse HTTP complète (headers + contenu)
     */
    public function send(): void
    {
        // Les headers ont déjà été envoyés dans addHeader/setStatusCode
        echo $this->content;
    }

}