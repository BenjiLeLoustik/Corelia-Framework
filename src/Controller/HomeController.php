<?php

/* ===== /src/Controllers/HomeController.php ===== */
namespace App\Controller;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;

/**
 * Contrôleur principal de l'application.
 * Étend le contrôleur de base Corelia pour gérer les routes et le rendu.
 */
class HomeController extends BaseController
{
    /**
     * Route principale '/' qui rend le template 'home/index.ctpl'.
     *
     * @return array Données à passer au template (ex: username, modules)
     */
    #[RouteAttribute(path: '/', template: 'home/index.ctpl')]
    public function index(): array
    {
        return [
            'username' => 'Alice',
            'modules' => ['Blog', 'Shop', 'Forum'],
        ];
    }

    /**
     * Route API '/api/ping' qui retourne une réponse JSON.
     *
     * @return array Données JSON retournées (status, date actuelle)
     */
    #[RouteAttribute(path: '/api/ping', response: 'jsonResponse')]
    public function ping(): array
    {
        return [
            'status' => 'ok',
            'now' => date('c'),
        ];
    }
}
