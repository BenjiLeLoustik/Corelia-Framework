<?php
header('X-Corelia-Workspace: true');
require dirname(__DIR__, 3) . '/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$workspaceName = basename(dirname(__DIR__)); // détecte automatiquement 'Test'

use Corelia\Kernel;
$kernel = new Kernel($workspaceName);
$kernel->handle();