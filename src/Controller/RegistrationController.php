<?php

namespace App\Controller;

use Symfony\Component\Validator\Constraints\DateTime;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, 
    UserPasswordHasherInterface $userPasswordHasher, 
    EntityManagerInterface $entityManager,
    MailerService $mailerService,
    TokenGeneratorInterface $tokenGeneratorInterface
    ): Response
    {
        $user = new User(); 
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             
            //TOKEN
            $tokenRegistration = $tokenGeneratorInterface->generateToken();
             
            //USER
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setCreatedAt(new \DateTime());
            //USER TOKEN

            $user->setTokenRegistration($tokenRegistration); 

            $entityManager->persist($user);
            $entityManager->flush();

            // MAILER SAND
            $mailerService->send(
                $user->getEmail(),
                'confirmation du compte utilisateur',
                'registration_confirmation.html.twig',
                [
                    'user'=> $user,
                    'token'=>$tokenRegistration,
                    'lifeTimeToken'=>$user->getTokenRegistrationLifeTime()->format('d-m-Y H:i:s')
                ]
            );

            $this->addFlash('success','Votre compte à bien été crée, Veuillez vérifier vos emails pour l\'activer');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify', name: 'account_verify')]
    public function verify(string $token, User $user){

    }
}
