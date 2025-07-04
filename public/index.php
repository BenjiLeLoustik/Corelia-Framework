<?php 

/* ===== /public/index.php ===== */


/**
 * Point d'entrée principal de l'application Corelia.
 * Initialise l'autoloader et démarre le noyau (Kernel).
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Active l'affichage des erreurs PHP (en développement)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Corelia\Kernel;

// Instancie le noyau principal et traite la requête HTTP
$kernel = new Kernel();
$kernel->handle();
