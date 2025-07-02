<?php

/* ===== /src/Controllers/HomeController.php ===== */

namespace App\Controller;

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
        // Affiche la vue d'accueil
        $view = __DIR__ . '/../Views/Home/index.ctpl';
        if( file_exists( $view ) ){
            echo file_get_contents( $view );
        }else{
            echo "<h1>Bienvenue sur votre première page CoreliaPHP §</h1>";
        }
    }

}