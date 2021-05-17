<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Classes\Mail;
use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {


        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        $data = $request->get('email');

        if ($data) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $request->get('email')]);
            // save in bdd the reset password
            if ($user) {
                $reset_password = new ResetPassword;

                $reset_password
                    ->setUser($user)
                    ->setToken(uniqid())
                    ->setCreatedAt(new DateTime());

                $this->em->persist($reset_password);
                $this->em->flush();

                // send a mail user for update password
                $url = $this->generateUrl('update_password', ['token' => $reset_password->getToken()]);

                $content = "Bonjour" . $user->getFullName() . "<br/>Vous avez démandé à rénitialiser votre mot de passe.<br/>";
                $content .= "Merci de bien vouloir <a href=" . $url . "> cliquer ici</a>";

                $mail = new Mail;
                $mail->send($user->getEmail(), $user->getFullName(), 'Réinitialiser mot de passe', $content);

                $this->addFlash('notice', 'Dans quelque seconde un mail vous a été transférer pour réinitialiser votre mot de passe.');
            } else {
                $this->addFlash('notice', 'Cette adresse email et inconnu');
            }
        }
        return $this->render('reset_password/reset.html.twig');
    }

    /**
     * @Route("/modifier-mon-mot-de-passe/{token}", name="update_password")
     */
    public function update_password(Request $request, $token, UserPasswordEncoderInterface $encoder)
    {
        $reset_password = $this->em->getRepository(ResetPassword::class)->findOneBy(['token' => $token]);

        if (!$reset_password) {
            return $this->redirectToRoute('reset_password');
        }

        $now = new DateTime();
        if ($now > $reset_password->getCreatedAt()->modify('+2 hour')) {
            $this->addFlash('notice', 'Votre demande mot de passe expiré. Merci de renouveller.');
            return $this->redirectToRoute('reset_password');
        }
        // Start view form
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_pwd = $form->get('new_password')->getData();

            // Encoding new_password
            $password = $encoder->encodePassword($reset_password->getUser(), $new_pwd);
            $reset_password->getUser()->setPassword($password);

            // Save bdd

            $this->em->flush();
            // Redirect on login
            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
