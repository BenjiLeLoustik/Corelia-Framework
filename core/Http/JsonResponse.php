<?php

/* ===== /Core/Http/JsonResponse.php ===== */

namespace Corelia\Http;

class JsonResponse extends Response
{

    public function __construct( $data = [], int $statusCode = 200 )
    {
        parent::setStatusCode( $statusCode );
        $this->addHeader( 'Content-Type', 'application/json' );
        $this->setContent( json_encode( $data ) );
    }

}