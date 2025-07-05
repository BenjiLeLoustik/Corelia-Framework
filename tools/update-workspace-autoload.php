<?php

$composerFile = __DIR__ . '/../composer.json'; // adapte le chemin si besoin
$workspaceDir = __DIR__ . '/../workspace';

$composer = json_decode(file_get_contents($composerFile), true);

// On prépare la section psr-4
if (!isset($composer['autoload']['psr-4'])) {
    $composer['autoload']['psr-4'] = [];
}

// On supprime les anciens mappings Workspace\
foreach ($composer['autoload']['psr-4'] as $ns => $path) {
    if (strpos($ns, 'Workspace\\') === 0) {
        unset($composer['autoload']['psr-4'][$ns]);
    }
}

// Pour chaque workspace, on ajoute un mapping
foreach (glob($workspaceDir . '/*', GLOB_ONLYDIR) as $dir) {
    $name = basename($dir);
    $namespace = "Workspace\\$name\\Controllers\\";
    $path = "workspace/$name/src/Controllers/";
    $composer['autoload']['psr-4'][$namespace] = $path;
}

// On sauvegarde le composer.json modifié
file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Affiche un message pour l'utilisateur
echo "composer.json mis à jour. Pensez à exécuter : composer dump-autoload\n";