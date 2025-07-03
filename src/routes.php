<?php

/* ===== /src/routes.php ===== */

use Corelia\Routing\Router;

/**
 * Fichier de configuration des routes de l'application.
 * Crée et configure le routeur principal, puis charge dynamiquement les routes des modules activés.
 */

// Création du routeur principal
$router = new Router();

// Enregistrement manuel d'une route principale
$router->add('GET', '/', ['App\Controller\HomeController', 'index']);

// Chargement dynamique des routes définies dans les modules activés
$modulesPath = __DIR__ . '/../modules';
foreach (scandir($modulesPath) as $dir) {
    if ($dir === '.' || $dir === '..') continue;
    $configFile = $modulesPath . '/' . $dir . '/config.json';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);

        // Si le module est activé et contient des routes, on les enregistre
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

// Retourne le routeur configuré pour l'application
return $router;