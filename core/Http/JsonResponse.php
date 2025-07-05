<?php

/* ===== /Core/Http/JsonResponse.php ===== */

namespace Corelia\Http;

/**
 * Réponse HTTP au format JSON pour Corelia.
 *
 * Cette classe facilite l'envoi de données JSON depuis un contrôleur.
 * Elle hérite de la classe Response et configure automatiquement le header Content-Type,
 * le code HTTP, et le corps de la réponse en JSON.
 *
 * Usage typique :
 *   return new JsonResponse(['status' => 'ok', 'data' => $result]);
 */
class JsonResponse extends Response
{
    /**
     * Constructeur de la réponse JSON.
     *
     * @param mixed $data       Données à encoder en JSON et à envoyer dans la réponse.
     *                          Peut être un tableau, un objet, etc.
     * @param int   $statusCode Code HTTP de la réponse (par défaut 200).
     */
    public function __construct($data = [], int $statusCode = 200)
    {
        // Définit le code HTTP de la réponse
        $this->setStatusCode($statusCode);

        // Ajoute le header indiquant le type de contenu JSON
        $this->addHeader('Content-Type', 'application/json; charset=utf-8');

        // Encode les données en JSON (UTF-8, sans échapper les slashes)
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Gestion d'erreur d'encodage JSON
        // Si l'encodage échoue, on retourne une erreur JSON générique et un code 500
        if ($json === false) {
            $json = json_encode(['error' => 'Erreur d\'encodage JSON']);
            $this->setStatusCode(500);
        }

        // Définit le corps de la réponse avec le JSON généré
        $this->setContent($json);
    }
}
