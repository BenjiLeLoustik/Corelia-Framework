<?php
$path = __DIR__ . '/modules/Admin/Views/layout.ctpl';
echo "Chemin brut: $path\n";
if (file_exists($path)) {
    echo "Fichier trouvé !\n";
} else {
    echo "Fichier NON trouvé !\n";
}
echo "realpath: " . realpath($path) . "\n";
