<?php

/* ===== /Core/Http/JsonResponse.php ===== */

namespace Corelia\Http;

/**
 * Réponse HTTP au format JSON pour Corelia.
 * Hérite de la classe Response et facilite l'envoi de données JSON.
 */
class JsonResponse extends Response
{

    /**
     * Constructeur.
     *
     * @param mixed $data               Données à encoder en JSON et à envoyer dans la réponse
     * @param int   $statusCode         Code HTTP de la réponse (par défaut 200)
     */
    public function __construct( $data = [], int $statusCode = 200 )
    {
        parent::setStatusCode( $statusCode );
        $this->addHeader( 'Content-Type', 'application/json' );
        $this->setContent( json_encode( $data ) );
    }

}