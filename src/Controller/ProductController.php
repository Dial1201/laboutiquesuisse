<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/nos-produits", name="products")
     */
    public function products(): Response
    {

        $products = $this->em->getRepository(Product::class)->findAll();

        return $this->render('product/products.html.twig', [
            'products' => $products
        ]);
    }

    /**
     * @Route("/produit/{slug<[a-z0-9-]+>}", name="product", methods={"GET"})
     */
    public function product(string $slug): Response
    {
        $product = $this->em->getRepository(Product::class)->findOneBy(['slug' => $slug]);
        if (!$product) {
            return $this->redirectToRoute('products');
        }

        return $this->render('product/show.product.html.twig', [
            'product' => $product
        ]);
    }
}
