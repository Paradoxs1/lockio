<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Entity\Organization;
use App\Entity\UserOrganization;
use App\Entity\StorageObject;
use App\Form\UserType;
use App\Form\DetailsType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\EmailService;
use App\Service\StorageService;
use Symfony\Component\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): void
    {
        throw new \Exception('This should never be reached!');
    }

    /**
     * @Route("/signup", name="app_user_registration")
     * @param Request $request
     * @param EmailService $emailService
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function app_user_registration(Request $request, EmailService $emailService, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        $form = $this->createForm(DetailsType::class)
            ->remove('plainPassword')
            ->remove('updatePassword');

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();

            if ($em->getRepository(User::class)->findBy(['email' => $data['email']])) {
                $form->get('email')->addError(new FormError($translator->trans('message.email_unique')));
            }

            if($form->isValid()) {
                $tokenExpiresAt = new \DateTime('now +24 hours');

                $user = new User();
                $user->setFirstname($data['firstname']);
                $user->setLastname($data['lastname']);
                $user->setEmail($data['email']);
                $user->setRoles(['ROLE_USER']);
                $hash = password_hash($data['email'] . microtime() . uniqid(), PASSWORD_BCRYPT, array('cost' => 12));
                $hash = str_replace('/', '', $hash);
                $user->setActivationHash($hash);
                $user->setTokenExpiresAt($tokenExpiresAt);
                $em->persist($user);

                $organization = new Organization();
                $organization->setName($data['name']);
                $organization->setAddress1($data['address1']);
                $organization->setAddress2($data['address2']);
                $organization->setAddress3($data['address3']);
                $organization->setZip($data['zip']);
                $organization->setCity($data['city']);
                $organization->setCountry($data['country']);
                $em->persist($organization);

                $userOrganization = new UserOrganization();
                $userOrganization->setOrganization($organization);
                $userOrganization->setUser($user);
                $em->persist($userOrganization);

                $em->flush();

                $emailService->sendRegistrationEmail($user);

                return $this->forward('\App\Controller\SecurityController::activationSent', array(
                    'email' => $user->getEmail()
                ));
            }
        }

        return $this->render(
            'security/registration/registration.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/signup/activation-sent", name="app_activation_sent")
     */
    public function activationSent(Request $request, $email)
    {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        return $this->render(
            'security/registration/activationSent.html.twig', [
                'email' => $email
            ]
        );
    }

    /**
     * @Route("/signup/password/{hash}", name="app_activate_account")
     * @param Request $request
     * @param string $hash
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param StorageService $storageService
     * @param EntityManagerInterface $em
     * @param User|null $user
     * @return Response
     * @throws \Exception
     * @ParamConverter("user", options={"mapping": {"hash": "activationHash"}})
     */
    public function activateAccount(Request $request, string $hash, UserPasswordEncoderInterface $passwordEncoder, StorageService $storageService, EntityManagerInterface $em, User $user = null): Response
    {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        if ($user && $user->getTokenExpiresAt() > new \DateTime()) {
            $form = $this->createForm(UserType::class)
                ->remove('firstname')
                ->remove('lastname')
                ->remove('email');

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setActivationHash();
                $password = $passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData());
                $user->setPassword($password);
                $user->setTokenExpiresAt();
                $em->persist($user);

                $userOrganizations = $user->getUserOrganizations();
                foreach ($userOrganizations as $userOrganization) {

                    $organization = $userOrganization->getOrganization();
                    $organization->setTrialEndsAt(new \DateTime('now +7 day'));

                    $so = $storageService->createBucket($userOrganization);
                    if ($so) {
                        $organization->addStorageObject($so);
                    } else {
                        $this->addFlash('error', 'message.error_during_creating_bucket');
                    }

                    $em->persist($organization);
                }

                $em->flush();

                return $this->redirectToRoute('app_account_activated');
            }

            return $this->render(
                'security/registration/passwordSet.html.twig', [
                    'form' => $form->createView()
                ]
            );
        } else {
            $this->addFlash('error', 'message.wrong_link');

            return $this->redirectToRoute('app_homepage');
        }
    }

    /**
     * @Route("/signup/activated", name="app_account_activated")
     */
    public function accountActivated() {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        return $this->render(
            'security/registration/accountActivated.html.twig', []
        );
    }

    /**
     * @Route("/password/reset", name="app_password_reset")
     * @param Request $request
     * @param EmailService $emailService
     * @param EntityManagerInterface $em
     * @param UserRepository $userRepository
     * @param TranslatorInterface $translator
     * @return RedirectResponse|Response
     */
    public function passwordReset(Request $request, EmailService $emailService, EntityManagerInterface $em, UserRepository $userRepository, TranslatorInterface $translator): Response
    {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        $form = $this->createForm(UserType::class)
            ->remove('firstname')
            ->remove('lastname')
            ->remove('plainPassword');

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $form->get('email')->addError(new FormError($translator->trans('message.email_not_found')));
            }

            if ($form->isValid()) {
                if ($user->getIsActive()) {
                    $tokenExpiresAt = new \DateTime('now +24 hours');
                    $hash = password_hash($email . microtime() . uniqid(), PASSWORD_BCRYPT, array('cost' => 12));
                    $hash = str_replace('/', '', $hash);
                    $user->setPasswordResetHash($hash);
                    $user->setTokenExpiresAt($tokenExpiresAt);
                    $em->persist($user);
                    $em->flush();

                    $emailService->sendPasswordResetEmail($user);

                    return $this->forward('\App\Controller\SecurityController::passwordLinkSent', array(
                        'email' => $email
                    ));
                } else {
                    $this->addFlash('error', 'message.wrong_link');

                    return $this->redirectToRoute('app_homepage');
                }
            }
        }

        return $this->render(
            'security/password/passwordReset.html.twig', [
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/password/link", name="app_password_link_sent")
     */
    public function passwordLinkSent(Request $request, $email)
    {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        return $this->render(
            'security/password/passwordLinkSent.html.twig', [
                'email' => $email
            ]
        );
    }

    /**
     * @Route("/password/new/{hash}", name="app_password_new")
     * @param Request $request
     * @param $hash
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $em
     * @param User|null $user
     * @return RedirectResponse|Response
     * @ParamConverter("user", options={"mapping": {"hash": "passwordResetHash"}})
     */
    public function passwordNew(Request $request, $hash, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $em, User $user = null): Response
    {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        if ($user && $user->getTokenExpiresAt() > new \DateTime()) {

            $form = $this->createForm(UserType::class)
                ->remove('firstname')
                ->remove('lastname')
                ->remove('email');

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $user->setPasswordResetHash();
                $user->setTokenExpiresAt();
                $password = $passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData());
                $user->setPassword($password);
                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('app_password_set');
            }

            return $this->render(
                'security/password/passwordNew.html.twig', [
                    'form' => $form->createView()
                ]
            );
        } else {
            $this->addFlash('error','message.wrong_link');

            return $this->redirectToRoute('app_homepage');
        }
    }

    /**
     * @Route("/password/set", name="app_password_set")
     */
    public function passwordSet() {
        if( $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            return $this->redirectToRoute('app_homepage');
        }

        return $this->render(
            'security/password/passwordSet.html.twig', []
        );
    }
}