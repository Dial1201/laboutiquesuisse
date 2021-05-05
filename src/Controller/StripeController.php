<?php

namespace App\Controller;

use App\Classes\Cart;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session", name="stripe_create_session")
     */
    public function stripe(Cart $cart)
    {
        $product_for_stripe = [];
        $YOUR_DOMAIN = 'https://127.0.0.1:8000';

        foreach ($cart->getCart() as $product) {
            $product_for_stripe[] = [
                'price_data' => [
                    'currency' => 'EUR',
                    'unit_amount' => $product['product']->getPrice(),
                    'product_data' => [
                        'name' => $product['product']->getName(),
                        'images' => [$YOUR_DOMAIN . '/public/uploads/' . $product['product']->getIllustration()],
                    ],
                ],
                'quantity' => $product['quantity'],
            ];
        }

        Stripe::setApiKey('sk_test_51HIHGBCnShIfYuF8HkKdMKNUupFbjOEPIuXXDT1d05cZ1irj7JlJik4CrttRRZMEnzX94SCgW6ZRzg6v53IQzCvq00X9aTCQtt');


        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                $product_for_stripe
            ]],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/success.html',
            'cancel_url' => $YOUR_DOMAIN . '/cancel.html'
        ]);
        $response = new JsonResponse(['id' => $checkout_session->id]);
        return $response;
    }
}
