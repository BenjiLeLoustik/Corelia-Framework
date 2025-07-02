<?php 

/* ===== /core/Module/ModuleManager.php ===== */

namespace Corelia\Module;

use Corelia\Routing\Router;
use Exception;

class ModuleManager
{

    protected string $modulesPath;
    protected array $modules = [];
    protected array $enabledModules = [];
    protected array $loadedModules = [];

    public function __construct( string $modulesPath )
    {
        $this->modulesPath = $modulesPath;
        $this->loadModulesConfig();
        $this->resolveDependencies();
    }

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
     * Résout les dépendances et active uniquement les modules dont les dépendances sont satisfaites.
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
     * Retourne la liste ordonnée des modules activés (avec dépendances résolues).
     */
    public function getEnabledModules(): array
    {
        return $this->enabledModules;
    }

    /**
     * Charge les routes de tous les modules activés dans le routeur.
     */
    public function registerModulesRoutes( Router $router ): void
    {
        foreach( $this->enabledModules as $module ){
            $config = $this->modules[ $module ];
            if( !empty( $config['routes'] ) ){
                foreach( $config['routes'] as $route ){
                    $router->add( $route['method'], $route['path'], $route['handler'] );
                }
            }
        }
    }

}