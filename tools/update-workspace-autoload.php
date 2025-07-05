<?php

/**
 * Script de mise à jour automatique du mapping PSR-4 dans composer.json pour tous les workspaces.
 *
 * Ce script :
 *   - Supprime tous les anciens mappings "Workspace\..."
 *   - Ajoute un mapping PSR-4 pour chaque workspace détecté dans /workspace
 *   - Sauvegarde le composer.json modifié
 *   - Affiche un message pour rappeler d'exécuter "composer dump-autoload"
 *
 * Usage :
 *   php tools/update-workspace-autoload.php
 */

// Chemin vers le composer.json (adapte si besoin)
$composerFile = __DIR__ . '/../composer.json';
$workspaceDir = __DIR__ . '/../workspace';

// Charge le composer.json dans un tableau associatif
$composer = json_decode(file_get_contents($composerFile), true);

// Prépare la section autoload/psr-4 si elle n'existe pas
if (!isset($composer['autoload']['psr-4'])) {
    $composer['autoload']['psr-4'] = [];
}

// Supprime tous les anciens mappings commençant par "Workspace\"
foreach ($composer['autoload']['psr-4'] as $ns => $path) {
    if (strpos($ns, 'Workspace\\') === 0) {
        unset($composer['autoload']['psr-4'][$ns]);
    }
}

// Pour chaque workspace présent dans le dossier /workspace,
// ajoute un mapping PSR-4 : "Workspace\Nom\Controllers\" => "workspace/Nom/src/Controllers/"
foreach (glob($workspaceDir . '/*', GLOB_ONLYDIR) as $dir) {
    $name = basename($dir);
    $namespace = "Workspace\\$name\\Controllers\\";
    $path = "workspace/$name/src/Controllers/";
    $composer['autoload']['psr-4'][$namespace] = $path;
}

// Sauvegarde le composer.json modifié avec un format lisible
file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Affiche un message d'information pour l'utilisateur
echo "composer.json mis à jour. Pensez à exécuter : composer dump-autoload\n";
