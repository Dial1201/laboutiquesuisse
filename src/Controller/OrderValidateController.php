<?php

namespace App\Controller;

use App\Classes\Cart;
use App\Classes\Mail;
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

        if ($order->getState() == 0) {
            // vider panier
            $cart->remove();

            $order->setState(1);

            $this->em->flush();

            //Envoyer un email de confirmation
            $mail = new Mail();
            $content = "Bonjour" . $order->getUser()->getFirstname() . "<br/>" . 'Merci pour votre commande' . "<br/>" . 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s,';
            $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre commande sur la boutique suisse est validée.', $content);
        }



        return $this->render('order_validate/success.html.twig', [
            'order' => $order
        ]);
    }
}
