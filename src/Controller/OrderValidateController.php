<?php

namespace App\Controller;

use App\Classes\Cart;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderValidateController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/commande/merci/{stripeSessionId<[a-zA-Z_0-9]+>}", name="order_validate")
     */
    public function success($stripeSessionId, Cart $cart): Response
    {
        $order = $this->em->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);

        if (!$order || $order->getUser() != $this->getUser()) {
            return $this->redirectToRoute('home');
        }
        // Modifier isPaid à 1
        if (!$order->getIsPaid()) {
        // vider panier
            $cart->remove();

            $order->setIsPaid(1);

            $this->em->flush();
            //Envoyer un email de confirmation
        }
        // Afficher les info utilisateur


        return $this->render('order_validate/success.html.twig', [
            'order' => $order
        ]);
    }
}
