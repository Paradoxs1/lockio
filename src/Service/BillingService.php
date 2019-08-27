<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Organization;

class BillingService
{
    private $em;
    private $accountingService;
    private $paymentService;
    private $storageService;
    private $baseUsageGb;
    private $baseUsagePrice;
    private $additionalGbPrice;

    public function __construct(
        EntityManagerInterface $em,
        AccountingService $accountingService,
        PaymentService $paymentService,
        StorageService $storageService,
        $baseUsageGb,
        $baseUsagePrice,
        $additionalGbPrice
    )
    {
        $this->em = $em;
        $this->accountingService = $accountingService;
        $this->paymentService = $paymentService;
        $this->storageService = $storageService;
        $this->baseUsageGb = $baseUsageGb;
        $this->baseUsagePrice = $baseUsagePrice;
        $this->additionalGbPrice = $additionalGbPrice;
    }

    private function getOrganizationsForBilling()
    {
        return $this->em->getRepository(Organization::class)->getOrganizationsForBilling();
    }

    private function calculateInvoice($organization, $spaceUsageGb)
    {
        $result['base_usage_gb'] = $this->baseUsageGb;
        $result['base_usage_price'] = $this->baseUsagePrice;
        $result['base_usage_text'] = 'Base package '.$this->baseUsageGb.' GB';
        $result['additional_usage_gb'] = ($spaceUsageGb > $this->baseUsageGb) ? $spaceUsageGb - $this->baseUsageGb : 0;
        $result['additional_gb_price'] = $this->additionalGbPrice;
        $result['additional_usage_text'] = 'Additional GB';

        return $result;
    }

    public function createTransactionsSendInvoices()
    {
        $result = true;

        // 0. Get organizations for invoicing
        $organizations = $this->getOrganizationsForBilling();
        foreach ($organizations as $organization)
        {
            //1. Get current space usage in GB
            $organizationUsageGb = ceil($this->storageService->getBucketSizeInGB($organization));

            //2. Calculate sum for invoice
            $calculatedData = $this->calculateInvoice($organization, $organizationUsageGb);

            //3. Create invoice in Bexio
            $invoice = $this->accountingService->createInvoice($organization, $calculatedData);
            if (!$invoice) {
                $result = false;
                continue;
            }

            //4. Create charge in Stripe
            $charge = $this->paymentService->createCharge($organization, $invoice);
            if (!$charge) {
                $result = false;
            }

            //5. Send invoice by Bexio
            $sendSuccess = $this->accountingService->sendInvoice($organization, $invoice);
            if (!$sendSuccess) {
                $result = false;
                continue;
            }

            if ($charge) {
                //6. Create payment in Bexio
                $payment = $this->accountingService->createPayment($invoice, $invoice->total);
                if (!$payment) {
                    $result = false;
                    continue;
                }
            }

            //7. Set new billing due date
            $billingDueDate = new \DateTime($organization->getBillingDueDate()->format('Y-m-d H:i:s'));
            $billingDueDate->add(new \DateInterval('P1M'));
            $organization->setBillingDueDate($billingDueDate);
            $this->em->persist($organization);
        }

        $this->em->flush();

        return $result;
    }
}