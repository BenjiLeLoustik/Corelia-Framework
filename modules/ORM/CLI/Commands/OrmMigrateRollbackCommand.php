<?php

/* ===== /OrmMigrateRollbackCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;
use PDO;

class OrmMigrateRollbackCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'orm:migrate:rollback';
    }

    public function getDescription(): string
    {
        return 'Annule la dernière migration appliquée.';
    }

    public function execute(array $argv): int
    {
        $migrationDir = __DIR__ . '/../Migration/';

        // Connexion PDO (même logique que dans OrmMigrateCommand)
        $dbHost = getenv('DB_HOST');
        $dbPort = getenv('DB_PORT');
        $dbName = getenv('DB_DATABASE');
        $dbUser = getenv('DB_USERNAME');
        $dbPass = getenv('DB_PASSWORD');
        $dbType = getenv('DB_CONNECTION') ?: 'mysql';

        $dsn = match ($dbType) {
            'mysql' => "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
            'pgsql' => "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName",
            default => null
        };

        if (!$dsn) {
            echo "\033[31mType de base de données non supporté : $dbType\033[0m\n\n";
            return 1;
        }

        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $e) {
            echo "\033[31mConnexion échouée : " . $e->getMessage() . "\033[0m\n\n";
            return 1;
        }

        // Créer la table migrations si elle n'existe pas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Récupérer la dernière migration appliquée
        $stmt = $pdo->query("SELECT id, migration FROM migrations ORDER BY applied_at DESC, id DESC LIMIT 1");
        $last = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$last) {
            echo "\033[33mAucune migration appliquée à annuler.\033[0m\n\n";
            return 0;
        }

        $lastMigration = $last['migration'];
        $lastId = $last['id'];

        echo "\n\033[1mAnnulation de la migration : $lastMigration\033[0m\n";

        // Supposons que pour chaque migration Version_xxx__desc.php il existe un fichier rollback Version_xxx__desc_rollback.php
        $rollbackFile = $migrationDir . str_replace('.php', '_rollback.php', $lastMigration);

        if (!file_exists($rollbackFile)) {
            echo "\033[31mFichier de rollback introuvable : $rollbackFile\033[0m\n";
            echo "Créez manuellement le rollback pour cette migration.\n\n";
            return 1;
        }

        $sql = include $rollbackFile;
        if (!$sql || !is_string($sql)) {
            echo "\033[31mLe fichier de rollback ne retourne aucun SQL valide.\033[0m\n\n";
            return 1;
        }

        try {
            $pdo->exec($sql);

            // Supprimer la migration de la table de suivi (par ID pour plus de sécurité)
            $delete = $pdo->prepare("DELETE FROM migrations WHERE id = ?");
            $delete->execute([$lastId]);

            echo "\033[32mRollback effectué avec succès.\033[0m\n\n";
        } catch (\PDOException $e) {
            echo "\033[31mErreur lors du rollback : " . $e->getMessage() . "\033[0m\n\n";
            return 1;
        }

        return 0;
    }
}