<?php

namespace App\Controller;

use App\Classes\Cart;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session/{reference}", name="stripe_create_session")
     */
    public function stripe(Cart $cart, $reference, EntityManagerInterface $em)
    {
        $product_for_stripe = [];
        $YOUR_DOMAIN = 'https://127.0.0.1:8000';


        $order = $em->getRepository(Order::class)->findOneBy(['reference' => $reference]);

        if (!$order) {
            new JsonResponse(['error' => 'order']);
        }


        foreach ($order->getOrderDetails()->getValues() as $product) {
            $product_object = $em->getRepository(Product::class)->findOneByName($product->getProduct());

            $product_for_stripe[] = [
                'price_data' => [
                    'currency' => 'EUR',
                    'unit_amount' => $product->getPrice(),
                    'product_data' => [
                        'name' => $product->getProduct(),
                        'images' => [$YOUR_DOMAIN . "/uploads/products/" . $product_object->getIllustration()],
                    ],
                ],
                'quantity' => $product->getQuantity(),
            ];
        }

        $product_for_stripe[] = [
            'price_data' => [
                'currency' => 'EUR',
                'unit_amount' => $order->getCarrierPrice(),
                'product_data' => [
                    'name' => $order->getCarrierName(),
                    'images' => [$YOUR_DOMAIN],
                ],
            ],
            'quantity' => 1,
        ];


        Stripe::setApiKey('sk_test_51HIHGBCnShIfYuF8HkKdMKNUupFbjOEPIuXXDT1d05cZ1irj7JlJik4CrttRRZMEnzX94SCgW6ZRzg6v53IQzCvq00X9aTCQtt');

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => [[
                $product_for_stripe
            ]],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}'
        ]);

        $order->setStripeSessionId($checkout_session->id);

        $em->flush();

        $response = new JsonResponse(['id' => $checkout_session->id]);
        return $response;
    }
}
