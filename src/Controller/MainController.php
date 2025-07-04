<?php

/* ===== /src/Controllers/HomeController.php ===== */
namespace App\Controller;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;
use Corelia\Http\Response;
use Corelia\Http\JsonResponse;
use Corelia\Http\RedirectResponse;

/**
 * Contrôleur principal de l'application.
 * Illustre la gestion de différents types de réponses HTTP.
 */
class MainController extends BaseController
{
    /**
     * Page d'accueil (HTML).
     *
     * @return Response
     */
    #[RouteAttribute(path: '/', name: 'main.index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Main/index.ctpl', []);
    }

    /**
     * API ping (JSON).
     *
     * @return JsonResponse
     */
    #[RouteAttribute(path: '/api/ping', name: 'api.ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'now'    => date('c'),
        ]);
    }

    /**
     * Redirection HTTP vers l'accueil.
     *
     * @return RedirectResponse
     */
    #[RouteAttribute(path: '/go-home', name: 'redirect.home', methods: ['GET'])]
    public function goHome(): RedirectResponse
    {
        return $this->redirect('/');
    }

    /**
     * Téléchargement d'un fichier texte.
     *
     * @return Response
     */
    #[RouteAttribute(path: '/download', name: 'download.text', methods: ['GET'])]
    public function download(): Response
    {
        $response = new Response();
        $response->addHeader('Content-Type', 'text/plain; charset=utf-8')
                 ->addHeader('Content-Disposition', 'attachment; filename="demo.txt"')
                 ->setContent("Ceci est le contenu du fichier de démo.");
        return $response;
    }
}
