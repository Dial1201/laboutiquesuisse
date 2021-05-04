<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Classes\Cart;
use App\Entity\Order;
use App\Form\OrderType;
use App\Entity\OrderDetails;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    /**
     * @Route("/commande", name="order")
     */
    public function index(Cart $cart, Request $request): Response
    {
        if (!$this->getUser()->getAddresses()->getValues()) {
            return $this->redirectToRoute('account_address_add');
        }

        $form = $this->createForm(OrderType::class, null, ['user' => $this->getUser()]);


        return $this->render('order/order.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart->getCart()
        ]);
    }

    /**
     * @Route("/commande/recapitulatif", name="order_recap", methods={"POST"})
     */
    public function aadOrderRecap(Cart $cart, Request $request): Response
    {


        $form = $this->createForm(OrderType::class, null, ['user' => $this->getUser()]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $date = new \DateTime();

            $carriers = $form->get('carriers')->getData();
            $delivery = $form->get('address')->getData();


            $delivery_content = $delivery->getFirstname() . ' ' . $delivery->getLastname();
            $delivery_content .=  '<br>' . $delivery->getPhone();

            if ($delivery->getCompany()) {
                $delivery_content .=  '<br>' . $delivery->getCompany();
            }
            $delivery_content .=  '<br>' . $delivery->getAddress();
            $delivery_content .=  '<br>' . $delivery->getPostal() . ' ' . $delivery->getCity();
            $delivery_content .=  '<br>' . $delivery->getCountry();

            // Enregistrer commande
            $order = new Order;
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($delivery_content);
            $order->setIsPaid(0);

            $this->em->persist($order);

            $product_for_stripe = [];
            $YOUR_DOMAIN = 'https://127.0.0.1:8000';

            //enregistrer produit
            foreach ($cart->getCart() as $product) {

                $orderDetails = new OrderDetails;
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);

                $this->em->persist($orderDetails);

                $product_for_stripe = [
                    'payment_method_types' => ['card'],

                    'price_data' => [
                        'currency' => 'EUR',
                        'unit_amount' => $product['product']->getPrice(),
                        'product_data' => [
                            'name' => $product['product']->getName(),
                            'images' => [$YOUR_DOMAIN . '/uploads/' . $product['product']->getIllustration()],
                        ],
                    ],
                    'quantity' => $product['quantity'],

                ];
            }
            dd($product_for_stripe);

            // $this->em->flush();

            Stripe::setApiKey('sk_test_51HIHGBCnShIfYuF8HkKdMKNUupFbjOEPIuXXDT1d05cZ1irj7JlJik4CrttRRZMEnzX94SCgW6ZRzg6v53IQzCvq00X9aTCQtt');




            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => 2000,
                        'product_data' => [
                            'name' => 'Stubborn Attachments',
                            'images' => ["https://i.imgur.com/EHyR2nP.png"],
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $YOUR_DOMAIN . '/success.html',
                'cancel_url' => $YOUR_DOMAIN . '/cancel.html',
            ]);

            dump($checkout_session->id);
            dump(['id' => $checkout_session->id]);
            dd($checkout_session);


            return $this->render('order/aadOrderRecap.html.twig', [
                'cart' => $cart->getCart(),
                'carrier' => $carriers,
                'delivery' => $delivery_content
            ]);
        }
        return $this->redirectToRoute('cart');
    }
}
