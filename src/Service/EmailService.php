<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class EmailService
{
    private $mailer;
    private $templating;
    private $requestStack;
    private $router;
    private $logger;
    private $from;
    private $domainName;

    const LOG_PREFIX = 'EMAIL';

    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $templating,
        RequestStack $requestStack,
        RouterInterface $router,
        LoggerInterface $logger,
        $from
    )
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->logger = $logger;
        $this->from = $from;

        $this->domainName = $this->requestStack->getCurrentRequest()->getHost();
    }

    private function addErrorLog($error)
    {
        $error = array_merge(['prefix' => self::LOG_PREFIX], $error);
        $this->logger->error(json_encode($error));
    }

    private function sendMail($from, $to, $subject, $body)
    {
        try
        {
            $message = (new \Swift_Message($subject))
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body,'text/html');

            $this->mailer->send($message);

            return true;
        }
        catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function sendRegistrationEmail($user)
    {
        return $this->sendMail(
            $this->from,
            $user->getEmail(),
            $this->domainName . ' - Activate your account',
            $this->templating->render('emails/registration.html.twig', [
                    'user' => $user,
                    'domainName' => $this->domainName,
                    'link' => $this->router->generate('app_activate_account', array('hash' => $user->getActivationHash()), UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            )
        );
    }

    public function sendPasswordResetEmail($user)
    {
        return $this->sendMail(
            $this->from,
            $user->getEmail(),
            $this->domainName . ' - Password reset',
            $this->templating->render('emails/passwordReset.html.twig', [
                    'user' => $user,
                    'link' => $this->router->generate('app_password_new', array('hash' => $user->getPasswordResetHash()), UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            )
        );
    }
}