<?php

/* ===== /modules/ORM/Commands/OrmMigrateCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;
use PDO;

class OrmMigrateCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'orm:migrate';
    }

    public function getDescription(): string
    {
        return 'Exécute toutes les migrations SQL du module ORM.';
    }

    public function execute(array $argv): int
    {
        // Chargement de la configuration BDD depuis .env
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
            echo "\033[31mType de base de données non supporté : $dbType\033[0m\n";
            return 1;
        }

        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass);
        } catch (\PDOException $e) {
            echo "\033[31mConnexion échouée : " . $e->getMessage() . "\033[0m\n";
            return 1;
        }

        $migrationDir = __DIR__ . '/../Migration/';
        $migrations = glob($migrationDir . 'Version_*.php');
        sort($migrations);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Récupérer les migrations déjà appliquées
        $stmt = $pdo->query("SELECT migration FROM migrations");
        $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $migrationsApplied = 0;

        foreach ($migrations as $file) {
            $migrationName = basename($file);
            if (in_array($migrationName, $appliedMigrations)) {
                echo "\033[33mMigration $migrationName déjà appliquée, skipping.\033[0m\n";
                continue;
            }

            echo "Exécution de la migration : $migrationName\n";
            $sql = include $file;
            try {
                $pdo->exec($sql);
                // Enregistrer la migration appliquée
                $insert = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
                $insert->execute([$migrationName]);
                echo "\033[32mSuccès.\033[0m\n";
                $migrationsApplied++;
            } catch (\PDOException $e) {
                echo "\033[31mErreur dans $migrationName : " . $e->getMessage() . "\033[0m\n";
                return 1;
            }
        }

        if ($migrationsApplied === 0) {
            echo "\033[36mAucune nouvelle migration à appliquer.\033[0m\n";
        } else {
            echo "\033[32mToutes les migrations ont été appliquées.\033[0m\n";
        }
        echo "\n";
        return 0;
    }
}