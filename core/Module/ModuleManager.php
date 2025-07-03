<?php 

/* ===== /core/Module/ModuleManager.php ===== */

namespace Corelia\Module;

use Corelia\Routing\Router;
use Corelia\Routing\RouteAttribute; // <-- Utilise bien RouteAttribute ici
use Exception;

/**
 * Gestionnaire des modules Corelia.
 * Permet de charger la configuration des modules, de résoudre les dépendances,
 * et d'enregistrer dynamiquement les routes des modules dans le routeur.
 */
class ModuleManager
{
    
    /**
     * Chemin vers le dossier des modules.
     * @var string
     */
    protected string $modulesPath;

    /**
     * Tableau associatif des modules activés avec leur configuration.
     * @var array
     */
    protected array $modules = [];

    /**
     * Liste ordonnée des modules activés (après résolution des dépendances).
     * @var array
     */
    protected array $enabledModules = [];

    /**
     * Liste des modules effectivement chargés (optionnel, pour extensions futures).
     * @var array
     */
    protected array $loadedModules = [];

    /**
     * Constructeur.
     * Charge la configuration des modules et résout les dépendances.
     *
     * @param string $modulesPath           Chemin du dossier des modules
     */
    public function __construct( string $modulesPath )
    {
        $this->modulesPath = $modulesPath;
        $this->loadModulesConfig();
        $this->resolveDependencies();
    }

    /**
     * Charge la configuration (config.json) de tous les modules activés.
     * Remplit la propriété $modules.
     */
    protected function loadModulesConfig(): void
    {
        foreach( scandir( $this->modulesPath ) as $dir ){
            if( $dir === '.' || $dir === '..' ) continue;
            $configFile = $this->modulesPath . '/' . $dir . '/config.json';
            if( file_exists( $configFile ) ){
                $config = json_decode( file_get_contents( $configFile ), true );
                if( !empty( $config['enabled'] ) ){
                    $this->modules[ $dir ] = $config;
                }
            }
        }
    }

    /**
     * Résout l'ordre de chargement des modules selon leurs dépendances.
     * Remplit la propriété $enabledModules.
     * Lance une exception en cas de dépendance manquante ou circulaire.
     */
    protected function resolveDependencies(): void
    {
        $resolved   = [];
        $unresolved = [];

        foreach( $this->modules as $name => $config ){
            $this->resolve( $name, $resolved, $unresolved );
        }

        $this->enabledModules = $resolved;
    }

    /**
     * Résolution récursive des dépendances pour un module donné.
     * @param string $module                Nom du module à résoudre
     * @param array  $resolved              Liste des modules déjà résolus (par référence)
     * @param array  $unresolved            Liste des modules en cours de résolution (par référence)
     * @throws                              Exception si dépendance manquante ou circulaire
     */
    protected function resolve( string $module, array &$resolved, array &$unresolved ): void
    {
        $unresolved[] = $module;
        $dependencies = $this->modules[ $module ]['dependencies'] ?? [];
        foreach( $dependencies as $dep ){
            if( !isset( $this->modules[ $dep ] ) ){
                throw new Exception("Le module $module dépend du module $dep qui n'est pas activé.");
            }
            if( !in_array( $dep, $resolved ) ){
                if( in_array( $dep, $unresolved ) ){
                    throw new Exception("Dépendance circulaire détectée entre $module et $dep.");
                }
                $this->resolve( $dep, $resolved, $unresolved );
            }
        }
        if( !in_array( $module, $resolved ) ){
            $resolved[] = $module;
        }
        unset( $unresolved[ array_search( $module, $unresolved ) ] );
    }

    /**
     * Retourne la liste ordonnée des modules activés.
     * @return array
     */
    public function getEnabledModules(): array
    {
        return $this->enabledModules;
    }

    /**
     * Enregistre les routes de tous les modules activés dans le routeur.
     * Prend en charge les routes déclarées dans config.json et les attributs #[RouteAttribute].
     *
     * @param Router $router                Instance du routeur Corelia
     */
    public function registerModulesRoutes(Router $router): void
    {
        foreach ($this->enabledModules as $module) {
            $config = $this->modules[$module];

            // 1. Routes déclarées dans config.json (compatibilité)
            if (!empty($config['routes'])) {
                foreach ($config['routes'] as $route) {
                    $router->add($route['method'], $route['path'], $route['handler']);
                }
            }

            // 2. Découverte automatique via attributs #[RouteAttribute]
            // Suppose que les contrôleurs sont dans le namespace Modules\{Module}\*
            $controllersPath = __DIR__ . "/../../modules/$module/";
            foreach (glob($controllersPath . '*Controller.php') as $file) {
                $className = "Modules\\$module\\" . basename($file, '.php');
                if (!class_exists($className)) {
                    require_once $file;
                }
                if (!class_exists($className)) continue;

                $rc = new \ReflectionClass($className);
                foreach ($rc->getMethods() as $method) {
                    foreach ($method->getAttributes(RouteAttribute::class) as $attr) {
                        $routeAttr = $attr->newInstance();
                        $router->add(
                            $routeAttr->methods,
                            $routeAttr->path,
                            [$className, $method->getName()]
                        );
                    }
                }
            }
        }
    }
}
