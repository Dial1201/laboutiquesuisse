<?php

namespace App\Controller;

use App\Classes\Cart;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    /**
     * @Route("/mon-panier", name="cart")
     */
    public function cart(Cart $cart, EntityManagerInterface $em): Response
    {
        $cartComplete = [];

        foreach ($cart->get() as $id => $quatity) {
            $cartComplete[] = [
                'product' => $em->getRepository(Product::class)->findOneBy(['id' => $id]),
                'quantity' => $quatity
            ];
        }

        return $this->render('cart/cart.html.twig', [
            'cart' => $cartComplete
        ]);
    }

    /**
     * @Route("/cart/add/{id<\d+>}", name="add_to_cart")
     */
    public function add(Cart $cart, $id): Response
    {
        $cart->add($id);
        return $this->redirectToRoute('cart');
    }

    /**
     * @Route("/cart/remove", name="remove_my_cart")
     */
    public function remove(Cart $cart): Response
    {
        $cart->remove();

        return $this->redirectToRoute('products');
    }
}
