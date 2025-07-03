<?php

/* ===== /modules/ORM/Commands/OrmMigrateStatusCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;
use PDO;

class OrmMigrateStatusCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'orm:migrate:status';
    }

    public function getDescription(): string
    {
        return 'Affiche le statut des migrations : appliquées ou en attente.';
    }

    public function execute(array $argv): int
    {
        $migrationDir = __DIR__ . '/../Migration/';
        $migrations = glob($migrationDir . 'Version_*.php');
        sort($migrations);

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
            echo "\033[31mType de base de données non supporté : $dbType\033[0m\n";
            return 1;
        }

        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_COLUMN,
            ]);
        } catch (\PDOException $e) {
            echo "\033[31mConnexion échouée : " . $e->getMessage() . "\033[0m\n";
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

        // Récupérer les migrations déjà appliquées
        $stmt = $pdo->query("SELECT migration FROM migrations");
        $appliedMigrations = $stmt->fetchAll();

        echo "\n\033[1mStatut des migrations :\033[0m\n\n";

        $applied = 0;
        $total = count($migrations);

        foreach ($migrations as $file) {
            $migrationName = basename($file);
            if (in_array($migrationName, $appliedMigrations)) {
                echo "  \033[32m✔ $migrationName\033[0m\n";
                $applied++;
            } else {
                echo "  \033[33m✘ $migrationName\033[0m\n";
            }
        }

        if ($total === 0) {
            echo "\033[33mAucune migration trouvée dans $migrationDir\033[0m\n";
        }

        echo "\n\033[1m$total migrations | appliquées = $applied | en attente = " . ($total - $applied) . "\033[0m\n\n";
        return 0;
    }
}