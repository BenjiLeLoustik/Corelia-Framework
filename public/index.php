<?php 

/* ===== /public/index.php ===== */

require_once __DIR__ . '/../vendor/autoload.php';

use Corelia\Kernel;

$kernel = new Kernel();
$kernel->handle();