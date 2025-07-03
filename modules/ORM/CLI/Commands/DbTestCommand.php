<?php

/* ===== /modules/ORM/Commands/DbTestCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;
use Modules\ORM\Entity\EntityManager;

class DbTestCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'db:test';
    }

    public function getDescription(): string
    {
        return 'Teste la connexion à la base de données et les opérations basiques de l\'EntityManager.';
    }

    public function execute(array $argv): int
    {
        echo "\n\033[1mTest de connexion à la base de données...\033[0m\n";

        try {
            $em = new EntityManager();
            echo "\033[32mConnexion réussie.\033[0m\n";
        } catch (\Exception $e) {
            echo "\033[31mErreur de connexion : {$e->getMessage()}\033[0m\n\n";
            return 1;
        }

        // Test de persistance d'une entité fictive si la classe existe
        $entityClass = 'Modules\ORM\Entity\Generated\TestEntity';

        if (class_exists($entityClass)) {
            echo "\n\033[1mTest de persistance sur $entityClass...\033[0m\n";
            $entity = new $entityClass();

            if (property_exists($entity, 'name')) {
                $entity->name = 'Test';
            }

            try {
                $em->persist($entity);
                echo "\033[32mInsertion réussie (id={$entity->id}).\033[0m\n";

                $found = $em->find($entityClass, $entity->id);
                if ($found) {
                    echo "\033[32mLecture réussie (id={$found->id}).\033[0m\n";
                } else {
                    echo "\033[33mLecture échouée après insertion.\033[0m\n";
                }

                $em->remove($entity);
                echo "\033[32mSuppression réussie.\033[0m\n";
            } catch (\Exception $e) {
                echo "\033[31mErreur lors des opérations ORM : {$e->getMessage()}\033[0m\n\n";
                return 1;
            }
        } else {
            echo "\033[33mAucune entité de test trouvée (Modules\ORM\Entity\Generated\TestEntity).\033[0m\n";
        }

        echo "\n";
        return 0;
    }
}