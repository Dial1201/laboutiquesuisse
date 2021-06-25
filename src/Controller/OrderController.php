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
     * @Route("/commande/recapitulatif", name="order_recap", methods={"POST","GET"})
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
            $reference = $date->format('dmY') . '-' . uniqid();
            $order->setReference($reference);
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($delivery_content);
            $order->setState(0);

            $this->em->persist($order);


            //enregistrer produit
            foreach ($cart->getCart() as $product) {

                $orderDetails = new OrderDetails;
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);

                $this->em->persist($orderDetails);
            }
            // dd($order);

            $this->em->flush();

            return $this->render('order/aadOrderRecap.html.twig', [
                'cart' => $cart->getCart(),
                'carrier' => $carriers,
                'delivery' => $delivery_content,
                'apiKeyPublic' => $_ENV["SP_APIKEY_PUBLIC"],
                'reference' => $order->getReference()
            ]);
        }
        return $this->redirectToRoute('cart');
    }
}
