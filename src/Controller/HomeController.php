<?php

namespace App\Controller;

use App\Classes\Mail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(Mail $mail): Response
    {
        $mail->send('fancy_95310@hotmail.com', 'Issa diallo', 'first email with symfony', 'Bonjour Issa nouveau mail');
        return $this->render('home/home.html.twig');
    }
}
