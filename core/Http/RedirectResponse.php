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
     * Constructeur.
     *
     * @param string $url                   URL de redirection
     * @param int    $statusCode            Code HTTP de redirection (par défaut 302)
     */
    public function __construct( string $url, int $statusCode = 302 )
    {
        parent::setStatusCode( $statusCode );
        header("location: $url", true, $statusCode);
        $this->setContent('');
    }

    /**
     * Envoie la réponse de redirection et termine le script.
     */
    public function send(): void
    {
        // Redirection : Rien à afficher, tout est fait dans le constructeur
        exit;
    }

}