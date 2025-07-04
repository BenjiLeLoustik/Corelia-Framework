<?php

/* ===== /Core/Http/Response.php ===== */

namespace Corelia\Http;

/**
 * Classe de base pour gérer une réponse HTTP.
 * Permet de définir le code de statut, les headers et le contenu,
 * puis d'envoyer la réponse complète au client.
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
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Contenu de la réponse (HTML, JSON, etc.)
     * @var string
     */
    protected string $content = '';

    /**
     * Définit le code HTTP de la réponse.
     *
     * @param int $code Code HTTP (ex: 200, 404)
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Retourne le code HTTP actuel de la réponse.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Ajoute ou remplace un header HTTP à la réponse.
     *
     * @param string $name  Nom du header (ex: Content-Type)
     * @param string $value Valeur du header
     * @return self
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Retourne tous les headers HTTP définis.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Définit le contenu de la réponse.
     *
     * @param string $content Contenu à envoyer (HTML, JSON, etc.)
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Retourne le contenu de la réponse.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Envoie tous les headers HTTP stockés, ainsi que le code de statut.
     *
     * @return void
     */
    public function sendHeaders(): void
    {
        // Code de statut HTTP
        http_response_code($this->statusCode);

        // Headers de sécurité par défaut
        header('X-Frame-Options: SAMEORIGIN', true);
        header('X-Content-Type-Options: nosniff', true);
        header('Referrer-Policy: no-referrer-when-downgrade', true);

        // Headers personnalisés
        foreach ($this->headers as $name => $value) {
            // Pour éviter les doublons de header
            header("$name: $value", true);
        }
    }

    /**
     * Envoie la réponse HTTP complète (headers + contenu).
     * Affiche le contenu au client.
     *
     * @return void
     */
    public function send(): void
    {
        $this->sendHeaders();
        echo $this->content;
    }
}
