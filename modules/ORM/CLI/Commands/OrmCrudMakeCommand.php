<?php

/* ====== /modules/ORM/Commands/OrmCrudMakeCommand.php ====== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;

class OrmCrudMakeCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'orm:crud:make';
    }

    public function getDescription(): string
    {
        return 'Génère un CRUD minimal pour une entité donnée.';
    }

    public function execute(array $argv): int
    {
        if (count($argv) < 3) {
            echo "Usage : php corelia orm:crud:make <NomEntite>\n";
            return 1;
        }

        $entity = ucfirst(preg_replace('/[^a-zA-Z0-9_]/', '', $argv[2]));
        $entityClass = "Modules\\ORM\\Entity\\Generated\\$entity";
        $controllerDir = __DIR__ . '/../Controllers/';
        $viewsDir = __DIR__ . '/../Views/' . strtolower($entity) . '/';

        if (!class_exists($entityClass)) {
            echo "\033[31mEntité $entityClass introuvable.\033[0m\n";
            echo "Génère l'entité avec : php corelia make:entity\n\n";
            return 1;
        }
        if (!is_dir($controllerDir)) mkdir($controllerDir, 0777, true);
        if (!is_dir($viewsDir)) mkdir($viewsDir, 0777, true);

        // Génération du contrôleur
        $controllerFile = $controllerDir . $entity . 'Controller.php';

        if (file_exists($controllerFile)) {
            echo "\033[33mLe contrôleur existe déjà : $controllerFile\033[0m\n\n";
            return 1;
        }

        $controllerCode = <<<PHP
<?php
namespace Modules\ORM\Controllers;

use Modules\ORM\Entity\EntityManager;
use Modules\ORM\Entity\Generated\\$entity;

class {$entity}Controller
{
    protected EntityManager \$em;

    public function __construct()
    {
        \$this->em = new EntityManager();
    }

    public function index()
    {
        // Liste tous les éléments
        // À compléter selon ton framework/rendu
    }

    public function show(\$id)
    {
        // Affiche un élément
    }

    public function create()
    {
        // Formulaire de création
    }

    public function store()
    {
        // Traite la création
    }

    public function edit(\$id)
    {
        // Formulaire de modification
    }

    public function update(\$id)
    {
        // Traite la modification
    }

    public function delete(\$id)
    {
        // Suppression
    }
}
PHP;

        file_put_contents($controllerFile, $controllerCode);
        echo "\033[32mContrôleur généré : $controllerFile\033[0m\n";

        // Génération des vues minimalistes
        $viewFiles = [
            'index.php' => "<!-- Liste des {$entity}s -->",
            'show.php'  => "<!-- Détail d'un $entity -->",
            'form.php'  => "<!-- Formulaire $entity -->",
        ];
        foreach ($viewFiles as $file => $content) {
            $fullPath = $viewsDir . $file;
            if (!file_exists($fullPath)) {
                file_put_contents($fullPath, $content);
                echo "\033[32mVue générée : $fullPath\033[0m\n";
            }
        }

        echo "\033[1;32mCRUD minimal généré pour $entity.\033[0m\n\n";
        return 0;
    }
}