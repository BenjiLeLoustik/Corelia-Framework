<?php

/* ===== /Core/Http/RedirectResponse.php ===== */

namespace Corelia\Http;

/**
 * Réponse HTTP de redirection pour Corelia.
 * Hérite de la classe Response et gère l'envoi d'un header Location.
 */
class RedirectResponse extends Response
{
    /**
     * Constructeur de la réponse de redirection.
     *
     * @param string $url        URL de redirection
     * @param int    $statusCode Code HTTP de redirection (par défaut 302)
     */
    public function __construct(string $url, int $statusCode = 302)
    {
        $this->setStatusCode($statusCode);
        $this->addHeader('Location', $url);
        $this->setContent(''); // Pas de contenu pour une redirection
    }

    /**
     * Envoie la réponse de redirection et termine le script.
     * Les headers sont envoyés, aucun contenu n'est affiché.
     *
     * @return void
     */
    public function send(): void
    {
        parent::sendHeaders();
        // Pour une redirection, il n'y a généralement pas de contenu
        exit;
    }
}
