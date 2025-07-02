<?php

/* ===== /src/routes.php ===== */

use Corelia\Routing\Router;

// Création du routeur
$router = new Router();

// Enregistrement des routes
$router->add('GET', '/', ['App\Controller\HomeController', 'index']);

// Chargement dynamique des routes des modules
$modulesPath = __DIR__ . '/../modules';
foreach (scandir($modulesPath) as $dir) {
    if ($dir === '.' || $dir === '..') continue;
    $configFile = $modulesPath . '/' . $dir . '/config.json';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if (!empty($config['enabled']) && !empty($config['routes'])) {
            foreach ($config['routes'] as $route) {
                $router->add(
                    $route['method'],
                    $route['path'],
                    $route['handler']
                );
            }
        }
    }
}

// Retourne le routeur configuré
return $router;