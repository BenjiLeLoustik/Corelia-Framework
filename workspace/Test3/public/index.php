<?php
header('X-Corelia-Workspace: true');
require dirname(__DIR__, 2) . '/vendor/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Corelia\Kernel;
$kernel = new Kernel('Test3');
$kernel->handle();