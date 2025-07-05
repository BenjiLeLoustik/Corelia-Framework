<?php

/* ===== /Core/Http/RedirectResponse.php ===== */

namespace Corelia\Http;

/**
 * Réponse HTTP de redirection pour Corelia.
 *
 * Cette classe facilite l'envoi d'une redirection HTTP depuis un contrôleur.
 * Elle hérite de la classe Response, configure automatiquement le header Location
 * et le code HTTP approprié (302 par défaut), et ne retourne pas de contenu.
 *
 * Usage typique :
 *   return new RedirectResponse('/login');
 */
class RedirectResponse extends Response
{
    /**
     * Constructeur de la réponse de redirection.
     *
     * @param string $url        URL de redirection (absolue ou relative)
     * @param int    $statusCode Code HTTP de redirection (par défaut 302)
     *                           301 = redirection permanente, 302 = temporaire
     */
    public function __construct(string $url, int $statusCode = 302)
    {
        // Définit le code HTTP de redirection
        $this->setStatusCode($statusCode);

        // Ajoute le header Location avec l'URL cible
        $this->addHeader('Location', $url);

        // Pas de contenu pour une réponse de redirection
        $this->setContent('');
    }

    /**
     * Envoie la réponse de redirection et termine le script.
     *
     * Cette méthode envoie tous les headers HTTP (dont Location), puis arrête l'exécution PHP.
     * Aucun contenu n'est affiché au client.
     *
     * @return void
     */
    public function send(): void
    {
        parent::sendHeaders();
        // Pour une redirection, il n'y a généralement pas de contenu à envoyer
        exit;
    }
}
