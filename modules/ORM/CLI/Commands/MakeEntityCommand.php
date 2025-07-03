<?php

/* ===== /modules/ORM/Commands/MakeEntityCommand.php ===== */

namespace Modules\ORM\Commands;

use Corelia\CLI\CommandInterface;

class MakeEntityCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'make:entity';
    }

    public function getDescription(): string
    {
        return 'Génère une entité, son repository et une migration.';
    }

    public function execute(array $argv): int
    {
        $base         = __DIR__ . '/../../';
        $entityDir    = $base . 'Entity/Generated/';
        $repoDir      = $base . 'Repository/';
        $migrationDir = $base . 'Migration/';

        if (!is_dir($entityDir)) mkdir($entityDir, 0777, true);
        if (!is_dir($repoDir)) mkdir($repoDir, 0777, true);
        if (!is_dir($migrationDir)) mkdir($migrationDir, 0777, true);

        // 1. Demander le nom de l'entité
        echo "Nom de l'entité à créer : ";
        $entity = trim(fgets(STDIN));
        if (!$entity) {
            echo "\033[31mNom d'entité requis.\033[0m\n";
            return 1;
        }
        $entity = ucfirst(preg_replace('/[^a-zA-Z0-9_]/', '', $entity)); // nettoyage

        // Vérifier si l'entité existe déjà
        $entityFile = $entityDir . $entity . '.php';
        if (file_exists($entityFile)) {
            echo "\033[31mL'entité '$entity' existe déjà.\033[0m\n";
            return 1;
        }

        // Collecte des champs et des relations
        $fields = [];
        $relations = [];

        while (true) {
            echo "Nom du champ (laisser vide pour terminer) : ";
            $fieldName = trim(fgets(STDIN));
            if ($fieldName === '') break;

            echo "Type (string, integer, text, datetime, float, bool) : ";
            $fieldType = strtolower(trim(fgets(STDIN)));

            $length = null;
            if ($fieldType === 'string') {
                echo "Taille (par défaut : 255) : ";
                $lengthInput = trim(fgets(STDIN));
                $length = $lengthInput !== '' ? (int)$lengthInput : 255;
            }

            echo "Nullable ? (yes/no) [no] : ";
            $nullableInput = strtolower(trim(fgets(STDIN)));
            $nullable = ($nullableInput === 'yes');

            // Détection automatique de clé étrangère
            $is_relation = false;
            $relatedEntity = null;
            $relationType = null;
            if (
                preg_match('/^id_(.+)$/', $fieldName, $match) ||
                preg_match('/^(.+)_id$/', $fieldName, $match)
            ) {
                $relatedEntity = ucfirst($match[1]);
                echo "Le champ '$fieldName' ressemble à une clé étrangère vers l'entité '$relatedEntity'.\n";
                echo "Voulez-vous ajouter une relation avec '$relatedEntity' ? (yes/no) [yes] : ";
                $relInput = strtolower(trim(fgets(STDIN)));
                if ($relInput === '' || $relInput === 'yes') {
                    echo "Type de relation : [1] ManyToOne [2] OneToOne [3] ManyToMany [4] OneToMany (défaut : [1]) : ";
                    $typeInput = trim(fgets(STDIN));
                    $relationType = match ($typeInput) {
                        '2' => 'OneToOne',
                        '3' => 'ManyToMany',
                        '4' => 'OneToMany',
                        default => 'ManyToOne'
                    };

                    $relations[] = [
                        'field'     => $fieldName,
                        'entity'    => $relatedEntity,
                        'type'      => $relationType
                    ];

                    $is_relation = true;
                }
            }

            $fields[] = [
                'name'              => $fieldName,
                'type'              => $fieldType,
                'length'            => $length,
                'nullable'          => $nullable,
                'is_relation'       => $is_relation,
                'related_entity'    => $is_relation ? $relatedEntity : null,
                'relation_type'     => $is_relation ? $relationType : null,
            ];
        }

        // Générer le code de l'entité
        $propertiesCode = '';
        $methodsCode    = '';

        foreach ($fields as $f) {
            if ($f['is_relation']) {
                $propertiesCode .= "    /**\n";
                $propertiesCode .= "     * @var \\Modules\\ORM\\Entity\\Generated\\{$f['related_entity']}\n";
                $propertiesCode .= "     * @relation {$f['relation_type']}\n";
                $propertiesCode .= "     */\n";
                $propertiesCode .= "    protected ?\\Modules\\ORM\\Entity\\Generated\\{$f['related_entity']} \${$f['related_entity']};\n";

                $ucName = ucfirst($f['related_entity']);
                $methodsCode .= <<<PHP

    public function get$ucName(): ?\\Modules\\ORM\\Entity\\Generated\\{$f['related_entity']}
    {
        return \$this->{$f['related_entity']};
    }

    public function set$ucName(?\\Modules\\ORM\\Entity\\Generated\\{$f['related_entity']} \${$f['related_entity']}): void
    {
        \$this->{$f['related_entity']} = \${$f['related_entity']};
    }

PHP;
            } else {
                $phpType = match ($f['type']) {
                    'integer'   => 'int',
                    'float'     => 'float',
                    'bool'      => 'bool',
                    'datetime'  => '\\DateTime',
                    default     => 'string',
                };

                $nullablePrefix = $f['nullable'] ? '?' : '';
                $propertiesCode .= "    protected {$nullablePrefix}{$phpType} \${$f['name']};\n";
                $ucName = ucfirst($f['name']);

                $methodsCode .= <<<PHP

    public function get$ucName(): {$nullablePrefix}{$phpType}
    {
        return \$this->{$f['name']};
    }

    public function set$ucName({$nullablePrefix}{$phpType} \${$f['name']}): void
    {
        \$this->{$f['name']} = \${$f['name']};
    }

PHP;
            }
        }

        $entityCode = <<<PHP
<?php
namespace Modules\ORM\Entity\Generated;

class $entity
{
    protected ?int \$id = null;

$propertiesCode
$methodsCode
}
PHP;

        file_put_contents($entityFile, $entityCode);
        echo "\033[32mEntité $entity générée dans $entityFile.\033[0m\n";

        // Générer le repository
        $repoFile = $repoDir . $entity . 'Repository.php';
        if (!file_exists($repoFile)) {
            $repoCode = <<<PHP
<?php
namespace Modules\ORM\Repository;

class {$entity}Repository
{
    // Méthodes personnalisées pour l'entité $entity
}

PHP;
            file_put_contents($repoFile, $repoCode);
            echo "\033[32mRepository {$entity}Repository généré dans $repoFile.\033[0m\n";
        } else {
            echo "\033[33mRepository {$entity}Repository existe déjà.\033[0m\n";
        }

        // Générer la migration SQL
        $timestamp = date('YmdHis');
        $tableName = strtolower($entity);
        $migFile = $migrationDir . "Version_{$timestamp}__create_{$tableName}_table.php";

        $fieldsSql = "id INT AUTO_INCREMENT PRIMARY KEY,\n";
        $foreignKeys = '';

        foreach ($fields as $f) {
            if ($f['is_relation']) {
                $fieldsSql .= "{$f['name']} INT" . ($f['nullable'] ? " NULL" : " NOT NULL") . ",\n";
                $foreignKeys .= "FOREIGN KEY ({$f['name']}) REFERENCES " . strtolower($f['related_entity']) . "(id),\n";
            } else {
                $sqlType = match ($f['type']) {
                    'string'    => "VARCHAR(" . ($f['length'] ?? 255) . ")",
                    'integer'   => "INT",
                    'float'     => "FLOAT",
                    'bool'      => "TINYINT(1)",
                    'datetime'  => "DATETIME",
                    'text'      => "TEXT",
                    default     => "VARCHAR(255)",
                };
                $fieldsSql .= "{$f['name']} $sqlType" . ($f['nullable'] ? " NULL" : " NOT NULL") . ",\n";
            }
        }

        $fieldsSql .= $foreignKeys;
        $fieldsSql = rtrim($fieldsSql, ",\n");

        $migCode = <<<PHP
<?php
// Migration pour créer la table $entity
return <<<SQL
CREATE TABLE $tableName (
$fieldsSql
);
SQL;

PHP;

        file_put_contents($migFile, $migCode);
        echo "\033[32mMigration générée dans $migFile.\033[0m\n";

        echo "\033[1;32mTout est prêt !\033[0m\n";
        return 0;
    }
}