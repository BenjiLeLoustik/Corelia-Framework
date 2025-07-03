<?php

/* ===== /src/Controllers/HomeController.php */
namespace App\Controller;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;

class HomeController extends BaseController
{
    #[RouteAttribute(path: '/', template: 'home/index.ctpl')]
    public function index(): array
    {
        return [
            'username' => 'Alice',
            'modules' => ['Blog', 'Shop', 'Forum'],
        ];
    }

    #[RouteAttribute(path: '/api/ping', response: 'jsonResponse')]
    public function ping(): array
    {
        return ['status' => 'ok', 'now' => date('c')];
    }
}
