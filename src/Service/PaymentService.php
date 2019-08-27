<?php

namespace App\Service;

use Symfony\Component\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

class PaymentService
{
    private $secretKey;
    private $translator;
    private $logger;

    const LOG_PREFIX = 'STRIPE';

    public function __construct(
        $secretKey,
        TranslatorInterface $translator,
        LoggerInterface $logger
    )
    {
        $this->secretKey = $secretKey;
        \Stripe\Stripe::setApiKey($this->secretKey);

        $this->translator = $translator;
        $this->logger = $logger;
    }

    private function addErrorLog($error)
    {
        $error = array_merge(['prefix' => self::LOG_PREFIX], $error);
        $this->logger->error(json_encode($error));
    }

    private function cardStripeToArray($cardStripe)
    {
        $cardArray['name'] = $cardStripe->name;
        $cardArray['last4'] = $cardStripe->last4;
        $cardArray['exp_month'] = $cardStripe->exp_month;
        $cardArray['exp_year'] = $cardStripe->exp_year;

        return $cardArray;
    }

    public function createCustomerWithCard($user, $token)
    {
        try
        {
            $customer = \Stripe\Customer::create([
                'source' => $token,
                'email' => $user->getEmail(),
            ]);

            return $customer;
        }
        catch (\Exception $e)
        {
            $this->addErrorLog([
                'operation' => '\Stripe\Customer::create',
                'user' => $user->getId(),
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function getCustomerLastCard($organization)
    {
        try
        {
            $list = \Stripe\Customer::retrieve($organization->getCcToken())->sources->all([
                'limit' => 1,
                'object' => 'card'
            ]);

            return $this->cardStripeToArray($list->data[0]);
        }
        catch (\Exception $e)
        {
            $this->addErrorLog([
                'operation' => '\Stripe\Customer::retrieve',
                'organization' => $organization->getId(),
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function addCardToCustomer($organization, $token)
    {
        try
        {
            $customer = \Stripe\Customer::retrieve($organization->getCcToken());
            $customer->source = $token;
            $customer->save();

            return $this->cardStripeToArray($customer->sources->data[0]);
        }
        catch (\Exception $e)
        {
            $this->addErrorLog([
                'operation' => '\Stripe\Customer::retrieve',
                'organization' => $organization->getId(),
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function createCharge($organization, $invoice)
    {
        try
        {
            $charge = \Stripe\Charge::create([
                'amount' => $invoice->total * 100,
                'currency' => 'chf',
                'customer' => $organization->getCcToken(),
                'description' => $this->translator->trans('stripeCharge.description') . ' / ' . $invoice->document_nr,
                'metadata' => ['order_id' => $invoice->id]
            ]);

            return $charge;
        }
        catch (\Exception $e)
        {
            $this->addErrorLog([
                'operation' => '\Stripe\Charge::create',
                'organization' => $organization->getId(),
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

}