<?php

/* ===== /modules/Admin/AdminController.php ===== */

namespace Modules\Admin;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;

/**
 * Contrôleur principal du module admin
 */
class AdminController extends BaseController
{
    /**
     * Affiche le tableau de bord de l'administration
     * (rendu template via annotation)
     */
    #[RouteAttribute(path: '/admin', template: 'Admin::dashboard.ctpl')]
    public function dashboard(): array
    {
        return ['theme' => 'dark'];
    }

}
