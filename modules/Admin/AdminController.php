<?php

/* ===== /modules/Admin/AdminController.php ===== */

namespace Modules\Admin;

use Corelia\Template\CoreliaTemplate;

/**
 * Contrôleur principal du module admin
 */
class AdminController
{

    /**
     * Affiche le tableau de bord de l'administration
     */
    public function dashboard()
    {
        echo "Dashboard admin OK";
        exit;
        /* $modules    = $this->getModules();
        $listHtml   = '';
        foreach( $modules as $name => $enabled ){
            $status     = $enabled ? 'Activé' : 'Désactivé';
            $listHtml   .= "<li>$name: $status</li>";
        }

        $tpl = new CoreliaTemplate( __DIR__ . '/Views/dashboard.ctpl' );
        $tpl->setLayout( __DIR__ . '/Views/base.ctpl' );
        echo $tpl->render([
            'title'     => 'Tableau de bord Admin',
            'modules'   => $listHtml,
            'year'      => date('Y')
        ]); */
    }

    /**
     * Récupère la liste des modules actifs et inactifs
     */
    public function getModules(): array
    {
        $modulesPath = __DIR__ . '/../';
        $modules = [];
        foreach (scandir($modulesPath) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $configFile = $modulesPath . $dir . '/config.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $modules[$dir] = $config['enabled'] ?? false;
            }
        }
        return $modules;
    }

}