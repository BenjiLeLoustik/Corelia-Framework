<?php

/* ===== /src/Controllers/HomeController.php */

namespace App\Controller;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;

class HomeController extends BaseController
{
    #[RouteAttribute(path: '/', template: 'welcome.ctpl')]
    public function index(): array
    {
        return ['theme' => 'light'];
    }
}
