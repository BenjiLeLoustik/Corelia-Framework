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
     * @param mixed $data       Données à encoder en JSON et à envoyer dans la réponse.
     * @param int   $statusCode Code HTTP de la réponse (par défaut 200).
     */
    public function __construct($data = [], int $statusCode = 200)
    {
        $this->setStatusCode($statusCode);
        $this->addHeader('Content-Type', 'application/json; charset=utf-8');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Gestion d'erreur d'encodage JSON
        if ($json === false) {
            $json = json_encode(['error' => 'Erreur d\'encodage JSON']);
            $this->setStatusCode(500);
        }

        $this->setContent($json);
    }
}
