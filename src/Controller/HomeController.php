<?php

/* ===== /src/Controllers/HomeController.php ===== */

namespace App\Controller;

use Corelia\Http\JsonResponse;
use Corelia\Http\RedirectResponse;
use Corelia\Http\Response;
use Corelia\Routing\RouteAttribute;
use Corelia\Template\CoreliaTemplate;

/**
 * Contrôleur d'exemple pour CoreliaPHP
 * Accessible via : /
 */
class HomeController
{

    /**
     * Action par défaut (Accueil)
     */
    #[RouteAttribute(path: '/', methods: ['GET'])]
    public function index(): Response
    {
        // Exemple de passage de variable à la vue
        $tpl = new CoreliaTemplate( __DIR__ . '/../Views/Home/index.ctpl' );
        $tpl->setLayout( __DIR__ . '/../Views/base.ctpl' );
        $html = $tpl->render([
            'nom'       => 'Développeur',
            'framework' => 'CoreliaPHP',
            'title'     => 'Accueil',
            'year'      => date('Y')
        ]);

        return (new \Corelia\Http\Response())->setContent($html);
    }

    #[RouteAttribute( path: '/json', methods: ['GET'] )]
    public function apiJson(): JsonResponse
    {
        return new JsonResponse(['user' => 'Jean', 'status' => 'ok']);
    }

    public function logout(): RedirectResponse
    {
        return new RedirectResponse('/login');
    }

}