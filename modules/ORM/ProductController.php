<?php

namespace Modules\ORM;

use Corelia\Controller\BaseController;
use Corelia\Routing\RouteAttribute;
use Corelia\Http\RedirectResponse;
use Modules\ORM\Entity\EntityManager;
use Modules\ORM\Entity\Generated\Product;
use Modules\ORM\Repository\ProductRepository;

/**
 * Contrôleur CRUD pour l'entité Product.
 *
 * Gère l'affichage, la création, la modification et la suppression de produits.
 * Utilise les routes via RouteAttribute et rend les templates .ctpl du module ORM.
 */
class ProductController extends BaseController
{
    protected EntityManager $em;
    protected ProductRepository $repo;

    /**
     * Initialise le contrôleur et ses dépendances.
     */
    public function __construct()
    {
        $this->em = new EntityManager();
        $this->repo = new ProductRepository();
    }

    /**
     * Affiche la liste des produits.
     *
     * @return array
     */
    #[RouteAttribute(path: '/product', template: 'ORM::Product/index.ctpl')]
    public function index(): array|RedirectResponse
    {
        $products = $this->repo->findAll();
        return ['products' => $products];
    }

    /**
     * Affiche le détail d'un produit.
     *
     * @param int $id
     * @return array|RedirectResponse
     */
    #[RouteAttribute(path: '/product/show/{id}', template: 'ORM::Product/show.ctpl')]
    public function show(int $id): array|RedirectResponse
    {
        $product = $this->repo->find($id);
        if (!$product) {
            return new RedirectResponse('/product?error=notfound');
        }
        error_log('SHOW CALLED, returning array');
        return ['product' => $product];
    }

    /**
     * Affiche le formulaire de création.
     *
     * @return array
     */
    #[RouteAttribute(path: '/product/create', template: 'ORM::Product/form.ctpl')]
    public function create(): array
    {
        return ['product' => new Product()];
    }

    /**
     * Traite la création d'un produit.
     *
     * @return RedirectResponse
     */
    #[RouteAttribute(path: '/product/store', methods: ['POST'])]
    public function store(): RedirectResponse
    {
        $product = new Product();
        foreach ($_POST as $key => $val) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($product, $setter)) {
                $product->$setter($val);
            }
        }
        $this->em->persist($product);
        return new RedirectResponse('/product');
    }

    /**
     * Affiche le formulaire d'édition d'un produit.
     *
     * @param int $id
     * @return array|RedirectResponse
     */
    #[RouteAttribute(path: '/product/edit/{id}', template: 'ORM::Product/form.ctpl')]
    public function edit(int $id): array|RedirectResponse
    {
        $product = $this->repo->find($id);
        if (!$product) {
            return new RedirectResponse('/product?error=notfound');
        }
        return ['product' => $product];
    }

    /**
     * Traite la modification d'un produit.
     *
     * @param int $id
     * @return RedirectResponse
     */
    #[RouteAttribute(path: '/product/update/{id}', methods: ['POST'])]
    public function update(int $id): RedirectResponse
    {
        $product = $this->repo->find($id);
        if (!$product) {
            return new RedirectResponse('/product?error=notfound');
        }
        foreach ($_POST as $key => $val) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($product, $setter)) {
                $product->$setter($val);
            }
        }
        $this->em->persist($product);
        return new RedirectResponse('/product');
    }

    /**
     * Supprime un produit.
     *
     * @param int $id
     * @return RedirectResponse
     */
    #[RouteAttribute(path: '/product/delete/{id}', methods: ['POST'])]
    public function delete(int $id): RedirectResponse
    {
        $product = $this->repo->find($id);
        if ($product) {
            $this->em->remove($product);
        }
        return new RedirectResponse('/product');
    }
}
