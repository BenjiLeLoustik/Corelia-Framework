<?php

namespace Modules\ORM\Repository;

use Modules\ORM\Entity\EntityManager;
use Modules\ORM\Entity\Generated\Product;

/**
 * Repository pour l'entité Product.
 * Ajoute ici tes méthodes de requêtes personnalisées.
 */
class ProductRepository
{
    protected EntityManager $em;

    public function __construct()
    {
        $this->em = new EntityManager();
    }

    /**
     * Retourne tous les produits.
     * @return Product[]
     */
    public function findAll(): array
    {
        $pdo = $this->em->getPdo();
        $stmt = $pdo->query("SELECT * FROM product");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $products = [];
        foreach ($rows as $row) {
            $product = new Product();
            foreach ($row as $key => $value) {
                $setter = 'set' . ucfirst($key);
                if (method_exists($product, $setter)) {
                    $product->$setter($value);
                }
            }
            $products[] = $product;
        }
        return $products;
    }

    /**
     * Trouve un produit par son ID.
     */
    public function find(int $id): ?Product
    {
        return $this->em->find(Product::class, $id);
    }

    /**
     * Recherche les produits par nom (exemple).
     * @param string $name
     * @return Product[]
     */
    public function findByName(string $name): array
    {
        $pdo = $this->em->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM product WHERE name LIKE ?");
        $stmt->execute(['%' . $name . '%']);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $products = [];
        foreach ($rows as $row) {
            $product = new Product();
            foreach ($row as $key => $value) {
                $setter = 'set' . ucfirst($key);
                if (method_exists($product, $setter)) {
                    $product->$setter($value);
                }
            }
            $products[] = $product;
        }
        return $products;
    }

    // Ajoute ici d'autres méthodes personnalisées selon tes besoins (findByCategory, countAll, etc.)
}
