<?php

/* ===== /modules/ORM/ORMController.php ===== */

namespace Modules\ORM;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;
use Corelia\Http\Response;

/** 
 * Contrôleur principal du module ORM.
 * Hérite de BaseController pour profiter des fonctionnalités communes aux contrôleurs.
 * 
 * @package Modules\ORM
 */
class ORMController extends BaseController
{
    /**
     * Affichage de la page ORM de ORM
     * 
     * @return array 
     */
    #[RouteAttribute(path: '/orm', template: 'ORM::index.ctpl')]
    public function index(): array
    {
        return [ "welcomeController" => "Votre module `ORM` a bien été créé !" ];
    }
}
