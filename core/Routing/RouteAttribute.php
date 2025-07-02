<?php

/* ===== /Core/Routing/RouteAttribute.php ===== */

namespace Corelia\Routing;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RouteAttribute
{
    public string $path;
    public array $methods;
    public ?string $template;
    public ?string $response;

    /**
     * @param string $path        Chemin de la route (ex: /admin)
     * @param array  $methods     Méthodes HTTP (ex: ['GET'])
     * @param string|null $template  Nom du template à rendre (ex: 'Admin::dashboard.ctpl')
     * @param string|null $response  Type de réponse spécial (ex: 'jsonResponse')
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
