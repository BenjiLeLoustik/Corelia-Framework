<?php

/* ===== /Core/Routing/RouteAttribute.php ===== */

namespace Corelia\Routing;

/**
 * Attribut PHP 8+ pour déclarer une route sur une méthode de contrôleur.
 *
 * Permet d'associer à une méthode :
 *   - un chemin d'URL,
 *   - une ou plusieurs méthodes HTTP,
 *   - un template à rendre,
 *   - un type de réponse (ex : JSON),
 *   - un nom unique de route (pour la génération d'URL ou la recherche).
 *
 * Exemple d'utilisation :
 * #[RouteAttribute(
 *     path: '/admin',
 *     methods: ['GET'],
 *     template: 'Admin/dashboard.ctpl',
 *     name: 'admin_dashboard'
 * )]
 *
 * Cet attribut est utilisé par le routeur pour découvrir et enregistrer automatiquement
 * les routes à partir des annotations des contrôleurs.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RouteAttribute
{
    /**
     * Chemin de la route (ex: '/admin')
     * @var string
     */
    public string $path;

    /**
     * Méthodes HTTP acceptées (ex: ['GET', 'POST'])
     * @var array<string>
     */
    public array $methods;

    /**
     * Nom du template à rendre (ex: 'Admin/dashboard.ctpl')
     * Peut être null si la méthode retourne une réponse personnalisée.
     * @var string|null
     */
    public ?string $template;

    /**
     * Type de réponse spécial (ex: 'jsonResponse' pour une API)
     * Peut être null pour une réponse HTML classique.
     * @var string|null
     */
    public ?string $response;

    /**
     * Nom unique de la route (pour la génération d'URL ou la recherche par nom)
     * Permet le reverse routing et la recherche rapide.
     * @var string|null
     */
    public ?string $name;

    /**
     * Constructeur de l'attribut RouteAttribute.
     *
     * @param string      $path      Chemin de la route (ex: '/admin')
     * @param array       $methods   Méthodes HTTP acceptées (ex: ['GET'])
     * @param string|null $template  Nom du template à rendre (optionnel)
     * @param string|null $response  Type de réponse spécial (optionnel, ex: 'jsonResponse')
     * @param string|null $name      Nom unique de la route (optionnel)
     */
    public function __construct(
        string $path,
        array $methods = ['GET'],
        ?string $template = null,
        ?string $response = null,
        ?string $name = null
    ) {
        $this->path     = $path;
        $this->methods  = $methods;
        $this->template = $template;
        $this->response = $response;
        $this->name     = $name;
    }
}
