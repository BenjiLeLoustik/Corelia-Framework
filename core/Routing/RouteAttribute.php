<?php

/* ===== /Core/Routing/RouteAttribute.php ===== */

namespace Corelia\Routing;

/**
 * Attribut PHP 8+ pour déclarer une route sur une méthode de contrôleur.
 * Permet d'associer un chemin, des méthodes HTTP, un template et un type de réponse.
 *
 * Exemple d'utilisation :
 * #[RouteAttribute(path: '/admin', methods: ['GET'], template: 'Admin/dashboard.ctpl')]
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RouteAttribute
{
    /**
     * Chemin de la route (ex: /admin)
     * @var string
     */
    public string $path;

    /**
     * Méthodes HTTP acceptées (ex: ['GET', 'POST'])
     * @var array
     */
    public array $methods;

    /**
     * Nom du template à rendre (ex: 'Admin/dashboard.ctpl')
     * @var string|null
     */
    public ?string $template;

    /**
     * Type de réponse spécial (ex: 'jsonResponse')
     * @var string|null
     */
    public ?string $response;

    /**
     * Constructeur de l'attribut RouteAttribute.
     *
     * @param string      $path         Chemin de la route
     * @param array       $methods      Méthodes HTTP (ex: ['GET'])
     * @param string|null $template     Nom du template à rendre (optionnel)
     * @param string|null $response     Type de réponse spécial (optionnel)
     */
    public function __construct(
        string $path,
        array $methods = ['GET'],
        ?string $template = null,
        ?string $response = null
    ) {
        $this->path = $path;
        $this->methods = $methods;
        $this->template = $template;
        $this->response = $response;
    }
}
