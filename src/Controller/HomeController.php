<?php

/* ===== /src/Controllers/HomeController.php ===== */

namespace App\Controller;

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
    public function index()
    {
        // Exemple de passage de variable à la vue
        $tpl = new CoreliaTemplate( __DIR__ . '/../Views/Home/index.ctpl' );
        echo $tpl->render([
            'nom' => 'Développeur',
            'framework' => 'CoreliaPHP'
        ]);
    }

}