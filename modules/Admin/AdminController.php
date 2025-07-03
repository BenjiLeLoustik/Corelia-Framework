<?php

/* ===== /modules/Admin/AdminController.php ===== */

namespace Modules\Admin;

use Corelia\Controller\BaseController;
use Corelia\Http\RedirectResponse;
use Corelia\Routing\RouteAttribute;

/**
 * Contrôleur principal du module d'administration.
 *
 * Ce contrôleur gère les routes et les actions liées à l'administration de l'application,
 * notamment l'affichage du tableau de bord et les statistiques sur les modules.
 *
 * Hérite de BaseController pour profiter des fonctionnalités communes aux contrôleurs.
 *
 * @package Modules\Admin
 */
class AdminController extends BaseController
{

    /**
     * Affiche le tableau de bord de l'administration.
     *
     * Cette méthode est routée sur l'URL "/admin" et utilise le template "Admin::dashboard.ctpl".
     * Elle retourne un tableau contenant des statistiques sur les modules :
     *  - enabled : nombre de modules activés
     *  - disabled : nombre de modules désactivés
     *  - available : nombre de modules disponibles (valeur fictive ici)
     *  - unavailable : nombre de modules indisponibles (valeur fictive ici)
     *
     * @return array            Statistiques des modules pour affichage dans le template d'administration.
     */
    #[RouteAttribute( path: '/admin', template: 'Admin::dashboard.ctpl' )]
    public function dashboard(): array
    {

        $this->getAllModulesMarket();

        $installedModules = $this->getAllInstalledModules();
        $modulesMarketplace = $this->getAllModulesMarket();
        
        $installedModulesByKey = [];
        foreach ($installedModules as $mod) {
            if (isset($mod['key'])) {
                $installedModulesByKey[(string)$mod['key']] = $mod;
            }
        }

        $modulesData = [];

        foreach ($modulesMarketplace as $marketplaceModule) {
            $key = (string)$marketplaceModule['key'];
            $isInstalled = array_key_exists($key, $installedModulesByKey);
            $isEnabled = $isInstalled && !empty($installedModulesByKey[$key]['enabled']);
            $isAvailable = $marketplaceModule['status'] === 'available';
            $isUnavailable = $marketplaceModule['status'] === 'unavailable';

            $modulesData[] = [
                'marketplaceModule' => $marketplaceModule,
                'isInstalled'       => $isInstalled,
                'isEnabled'         => $isEnabled,
                'isAvailable'       => $isAvailable,
                'isUnavailable'     => $isUnavailable,
            ];
        }

        $stats = [
            "enabled"       => $this->countEnabledModules(true),
            "disabled"      => $this->countEnabledModules(false),
            "available"     => $this->countAvailablesModulesMarket(),
            "unavailable"   => $this->countUnavailableModulesMarket()
        ];

        return [
            "stats" => $stats,
            "modulesData" => $modulesData
        ];
    }

    #[RouteAttribute( path: '/modules/{moduleName}/{status}', template: 'Admin::dashboard.ctpl' )]
    public function moduleToggleStatus( string $moduleName, $status ): RedirectResponse
    {
        $php = escapeshellarg( getenv('PHP_PATH') ); // adapte ce chemin si besoin
        $corelia = escapeshellarg(__DIR__ . '/../../corelia'); // adapte selon la structure de ton projet
        $moduleName = escapeshellarg($moduleName);

        if ($status === 'enabled') {
            $cmd = "$php $corelia module:enable $moduleName";
        } elseif ($status === 'disabled') {
            $cmd = "$php $corelia module:disable $moduleName";
        } else {
            // gestion d'erreur
            return new RedirectResponse('/admin?error=badstatus');
        }

        $output = shell_exec($cmd);
        // Pour debug : var_dump($cmd, $output); exit;
        return new RedirectResponse('/admin');
    }


    /**
     * Récupère la liste de tous les modules installés localement.
     *
     * Cette méthode parcourt le dossier /Modules/, lit chaque fichier config.json
     * de chaque module, et ajoute sa configuration au tableau retourné.
     * Seuls les fichiers de configuration valides (décodés en tableau) sont pris en compte.
     *
     * @return array            Liste des configurations des modules installés localement.
     */
    public function getAllInstalledModules(): array
    {
        $modulesDir = __DIR__ . "/../../Modules/";
        $modulesList = [];

        foreach (glob($modulesDir . '*/config.json') as $configFile) {
            $config = json_decode(file_get_contents($configFile), true);
            if (is_array($config)) {
                $modulesList[] = $config;
            }
        }

        return $modulesList;
        
    }


    /**
     * Compte le nombre de modules activés ou désactivés dans le framework.
     *
     * Cette méthode utilise la liste des modules installés (via getAllModules()),
     * puis incrémente un compteur pour chaque module dont la clé "enabled"
     * correspond à la valeur recherchée.
     *
     * @param bool $enabled     Statut à rechercher : true pour les modules activés, false pour les désactivés.
     * @return int              Nombre de modules correspondant au statut demandé.
     */
    public function countEnabledModules(bool $enabled = true): int
    {
        
        $modulesCount = 0;
        $modulesList = $this->getAllInstalledModules();

        foreach ($modulesList as $config) {
            if (isset($config['enabled']) && $config['enabled'] === $enabled) {
                $modulesCount++;
            }
        }

        return $modulesCount;
        
    }

    /**
     * Récupère la liste de tous les modules disponibles sur la marketplace en ligne.
     *
     * Cette méthode télécharge et décode le fichier modules.json depuis le dépôt GitHub officiel,
     * puis retourne son contenu sous forme de tableau associatif.
     *
     * @return array            Liste des modules sur la marketplace.
     */
    public function getAllModulesMarket(): array
    {
        $modulesMarketPlace    = 'https://benjileloustik.github.io/Corelia-Extras/modules.json';
        $modulesMarketPlace    = json_decode( file_get_contents( $modulesMarketPlace ), true );
        return $modulesMarketPlace;
    }

    /**
     * Compte le nombre de modules disponibles ("available") sur la marketplace.
     *
     * Parcourt la liste des modules récupérée via getAllModulesMarket() et incrémente le compteur
     * pour chaque module dont le statut est "available".
     *
     * @return int              Nombre de modules disponibles.
     */
    public function countAvailablesModulesMarket(): int
    {
        $marketPlaceModules = $this->getAllModulesMarket();
        $modulesCount       = 0;

        foreach( $marketPlaceModules as $module ){
            if( $module['status'] === 'available' ){
                $modulesCount++;
            }
        }

        return $modulesCount;
    }

    /**
     * Compte le nombre de modules indisponibles ("unavailable") sur la marketplace.
     *
     * Parcourt la liste des modules récupérée via getAllModulesMarket() et incrémente le compteur
     * pour chaque module dont le statut est "unavailable".
     *
     * @return int              Nombre de modules indisponibles.
     */
    public function countUnavailableModulesMarket(): int
    {
        $marketPlaceModules = $this->getAllModulesMarket();
        $modulesCount       = 0;

        foreach( $marketPlaceModules as $module ){
            if( $module['status'] === 'unavailable' ){
                $modulesCount++;
            }
        }

        return $modulesCount;
    }

}
