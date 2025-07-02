<?php

/* ===== /Core/Http/RedirectResponse.php ===== */

namespace Corelia\Http;

class RedirectResponse extends Response
{

    public function __construct( string $url, int $statusCode = 302 )
    {
        parent::setStatusCode( $statusCode );
        header("location: $url", true, $statusCode);
        $this->setContent('');
    }

    public function send(): void
    {
        // Redirection : Rien à afficher, tout est fait dans le constructeur
        exit;
    }

}