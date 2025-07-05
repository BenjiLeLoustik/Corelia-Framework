<?php

/* ===== /Core/Http/Response.php ===== */

namespace Corelia\Http;

/**
 * Classe de base pour gérer une réponse HTTP.
 *
 * Permet de définir le code de statut, les headers et le contenu,
 * puis d'envoyer la réponse complète au client.
 * Toutes les autres réponses (JSON, redirection, etc.) héritent de cette classe.
 *
 * Usage typique :
 *   $response = new Response();
 *   $response->setStatusCode(200)
 *            ->addHeader('Content-Type', 'text/html')
 *            ->setContent('<h1>Hello</h1>')
 *            ->send();
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
     * @return self Permet le chaînage des appels (fluent interface)
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Retourne le code HTTP actuel de la réponse.
     *
     * @return int Code HTTP de la réponse
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
     * @return self Permet le chaînage des appels
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Retourne tous les headers HTTP définis.
     *
     * @return array<string, string> Tableau associatif des headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Définit le contenu de la réponse.
     *
     * @param string $content Contenu à envoyer (HTML, JSON, etc.)
     * @return self Permet le chaînage des appels
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Retourne le contenu de la réponse.
     *
     * @return string Contenu de la réponse
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Envoie tous les headers HTTP stockés, ainsi que le code de statut.
     *
     * Ajoute aussi des headers de sécurité par défaut (X-Frame-Options, etc.).
     * Les headers personnalisés sont envoyés ensuite.
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
     *
     * Appelle sendHeaders() puis affiche le contenu au client.
     * À utiliser en fin de traitement d'une requête.
     *
     * @return void
     */
    public function send(): void
    {
        $this->sendHeaders();
        echo $this->content;
    }
}
