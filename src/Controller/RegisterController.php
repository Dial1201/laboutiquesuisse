<?php

namespace App\Controller;

use App\Classes\Mail;
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
        $notification = null;
        $user = new User;

        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $search_email = $this->em->getRepository(User::class)->findOneByEmail([$user->getEmail()]);
            if (!$search_email) {
                $pass = $encoder->encodePassword($user, $user->getPassword());

                $user = $user->setPassword($pass);

                $this->em->persist($user);
                $this->em->flush();

                $mail = new Mail();
                $content = "Bonjour" . $user->getFirstname() . "<br/>" . 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Maxime et magnam totam quo dolorum ipsum in ipsa vero, reiciendis perferendi';
                $mail->send($user->getEmail(), $user->getFirstname(), 'Bienvenue sur la boutique suisse', $content);

                $notification = "Votre inscription c'est correctement déroulée. Vous pouvez vous connecter.";

                return $this->redirectToRoute('app_login');
            } else {
                $notification = "L'email ou le mot de passe est déjà renseigné.";
            }
        }

        return $this->render('register/register.html.twig', [
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
