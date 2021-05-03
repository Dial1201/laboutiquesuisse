<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegisterController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/inscription", name="register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $user = new User;

        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $pass = $encoder->encodePassword($user, $user->getPassword());

            $user = $user->setPassword($pass);

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('register/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
