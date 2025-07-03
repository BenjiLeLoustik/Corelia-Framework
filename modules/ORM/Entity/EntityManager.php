<?php

namespace Modules\ORM\Entity;

use PDO;
use PDOException;

/**
 * EntityManager maison pour la gestion des entités ORM.
 * Gère la persistance, la récupération, la mise à jour et la suppression d'entités.
 */
class EntityManager
{
    /** @var PDO */
    protected PDO $pdo;

    /**
     * Initialise la connexion PDO à partir des variables d'environnement (.env).
     * Supporte MySQL et PostgreSQL.
     *
     * @throws \Exception Si la connexion échoue ou si le type de base n'est pas supporté
     */
    public function __construct()
    {
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_DATABASE') ?: 'corelia_db';
        $dbUser = getenv('DB_USERNAME') ?: 'root';
        $dbPass = getenv('DB_PASSWORD') ?: '';
        $dbType = getenv('DB_CONNECTION') ?: 'mysql';

        $dsn = match ($dbType) {
            'mysql' => "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
            'pgsql' => "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName",
            default => throw new \Exception("Type de base de données non supporté : $dbType"),
        };

        try {
            $this->pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Connexion à la base de données échouée : " . $e->getMessage());
        }
    }

    /**
     * Retourne la connexion PDO.
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Persiste une entité (insert ou update selon la présence de l'id).
     * @param object $entity
     * @return void
     */
    public function persist(object $entity): void
    {
        $class = get_class($entity);
        $table = $this->getTableName($class);
        $props = $this->getEntityProperties($entity);

        if (isset($props['id']) && $props['id']) {
            // UPDATE
            $fields = [];
            $values = [];
            foreach ($props as $k => $v) {
                if ($k === 'id') continue;
                $fields[] = "$k = ?";
                $values[] = $v;
            }
            $values[] = $props['id'];
            $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
        } else {
            // INSERT
            $fields = [];
            $placeholders = [];
            $values = [];
            foreach ($props as $k => $v) {
                if ($k === 'id') continue;
                $fields[] = $k;
                $placeholders[] = '?';
                $values[] = $v;
            }
            $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            // Hydrate l'id généré
            $id = $this->pdo->lastInsertId();
            $setter = 'setId';
            if (method_exists($entity, $setter)) {
                $entity->$setter($id);
            }
        }
    }

    /**
     * Trouve une entité par son id.
     * @param string $class Nom de la classe (FQCN)
     * @param int $id Identifiant
     * @return object|null
     */
    public function find(string $class, int $id): ?object
    {
        $table = $this->getTableName($class);
        $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $entity = new $class();
        foreach ($row as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        return $entity;
    }

    /**
     * Supprime une entité.
     * @param object $entity
     * @return void
     */
    public function remove(object $entity): void
    {
        $class = get_class($entity);
        $table = $this->getTableName($class);
        $props = $this->getEntityProperties($entity);
        if (!isset($props['id']) || !$props['id']) return;
        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$props['id']]);
    }

    /**
     * Retourne le nom de la table SQL pour une classe d'entité.
     * @param string $class
     * @return string
     */
    protected function getTableName(string $class): string
    {
        // Exemple : Modules\ORM\Entity\Generated\Product => product
        $parts = explode('\\', $class);
        return strtolower($parts[array_key_last($parts)]);
    }

    /**
     * Extrait les propriétés d'une entité via les getters (jamais d'accès direct !)
     * @param object $entity
     * @return array
     */
    protected function getEntityProperties(object $entity): array
    {
        $props = [];
        $refl = new \ReflectionClass($entity);
        foreach ($refl->getProperties() as $prop) {
            $name = $prop->getName();
            $getter = 'get' . ucfirst($name);
            if (method_exists($entity, $getter)) {
                $props[$name] = $entity->$getter();
            }
        }
        return $props;
    }
}
