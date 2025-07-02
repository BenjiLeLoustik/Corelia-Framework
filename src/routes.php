<?php

/* ===== /src/routes.php ===== */

use Corelia\Routing\Router;

// Création du routeur
$router = new Router();

// Enregistrement des routes
$router->add('GET', '/', ['App\Controller\HomeController', 'index']);
$router->add('GET', '/admin', ['App\Controller\AdminController', 'dashboard']);

// Exemple de route avec paramètre
$router->add('GET', '/blog/{id}', ['App\Controller\BlogController', 'show']);

// Retourne le routeur configuré
return $router;