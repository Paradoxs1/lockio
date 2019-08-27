<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Country;

class AccountingService
{
    private $em;
    private $translator;
    private $templating;
    private $logger;
    private $companyId;
    private $userId;
    private $publicKey;
    private $signatureKey;
    private $baseUrl;
    private $from;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    const LOG_PREFIX = 'BEXIO';

    private static $successFullHttpCodes = array(200, 201, 204);

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        \Twig_Environment $templating,
        LoggerInterface $logger,
        $companyId,
        $userId,
        $publicKey,
        $signatureKey,
        $baseUrl,
        $from
    )
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->logger = $logger;
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->publicKey = $publicKey;
        $this->signatureKey = $signatureKey;
        $this->baseUrl = $baseUrl;
        $this->from = $from;

        $this->baseUrl = $this->baseUrl . $this->companyId . '/' .  $this->userId . '/' . $this->publicKey;
    }

    private function addErrorLog($error)
    {
        $error = array_merge(['prefix' => self::LOG_PREFIX], $error);
        $this->logger->error(json_encode($error));
    }

    private function createUrl($route)
    {
        $url = $this->baseUrl . $route;
        return $url;
    }

    private function createSignature($method, $url, $postData = '')
    {
        switch ($method) {
            case self::METHOD_GET:
                $signature = strtolower($method) . $url . $this->signatureKey;
                break;
            case self::METHOD_POST:
                $signature = strtolower($method) . $url . $postData . $this->signatureKey;
                break;
            default:
                break;
        }

        $signature = md5($signature);
        return $signature;
    }

    private function curlCall($method, $url, $signature, $postData = '')
    {
        $curl = curl_init();
        $headers = array();
        $headers[] = "Accept: application/json";
        $headers[] = "Signature: ".$signature;

        switch ($method) {
            case self::METHOD_GET:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
            case self::METHOD_POST:
                curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($curl, CURLOPT_POST, 1);
                $headers[] = "Content-Type: application/x-www-form-urlencoded";
                break;
            default:
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);
        if (isset($curlInfo['http_code']) && !in_array($curlInfo['http_code'], self::$successFullHttpCodes)) {
            $this->addErrorLog([
                'url' => $url,
                'result' => $result
            ]);

            return false;
        }
        curl_close($curl);

        $result = json_decode($result);
        return $result;
    }

    private function generateAddressStr($organization)
    {
        $address = $organization->getAddress1() ? $organization->getAddress1() : '';
        $address .= $organization->getAddress2() ? " \n" . $organization->getAddress2() : '';
        $address .= $organization->getAddress3() ? " \n" . $organization->getAddress3() : '';

        return $address;
    }

    public function getContactList()
    {
        $url = $this->createUrl('/contact');
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    public function createContact($organization)
    {
        //check if countries synchronized
        if (!$organization->getCountry()->getBexioId())
            return false;

        $postData['name_1'] = $organization->getName();
        $postData['user_id'] = $this->userId;
        $postData['owner_id'] = $this->userId;
        $postData['contact_type_id'] = 1;
        $postData['address'] = $this->generateAddressStr($organization);
        $postData['postcode'] = $organization->getZip();
        $postData['city'] = $organization->getCity();
        $postData['country_id'] = $organization->getCountry()->getBexioId();

        $postData = json_encode($postData);

        $url = $this->createUrl('/contact');
        $signature = $this->createSignature(self::METHOD_POST, $url, $postData);

        $contact = $this->curlCall(self::METHOD_POST, $url, $signature, $postData);

        if ($contact)
        {
            return $contact->id;
        }

        return false;
    }

    public function updateContact($organization)
    {
        //check if countries synchronized
        if (!$organization->getCountry()->getBexioId())
            return false;

        $postData['address'] = $this->generateAddressStr($organization);
        $postData['postcode'] = $organization->getZip();
        $postData['city'] = $organization->getCity();
        $postData['country_id'] = $organization->getCountry()->getBexioId();

        $postData = json_encode($postData);

        $url = $this->createUrl('/contact/'.$organization->getBexioId());
        $signature = $this->createSignature(self::METHOD_POST, $url, $postData);

        $contact = $this->curlCall(self::METHOD_POST, $url, $signature, $postData);

        if ($contact)
        {
            return $contact->id;
        }

        return false;
    }

    public function getCountryList()
    {
        $url = $this->createUrl('/country');
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    public function syncCountries()
    {
        $bexioCountries = $this->getCountryList();
        if (!$bexioCountries)
            return false;

        foreach ($bexioCountries as $bexioCountry)
        {
            if ($country = $this->em->getRepository(Country::class)->findOneBy(['isoAlpha2' => $bexioCountry->iso_3166_alpha2])) {
                $country->setBexioId($bexioCountry->id);
                $this->em->persist($country);
            }
        }

        $this->em->flush();

        return true;
    }

    public function getInvoiceList()
    {
        $url = $this->createUrl('/kb_invoice');
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    public function getContactInvoices($organization)
    {
        if (!$organization->getBexioId()) {
            return false;
        }

        $params['field'] = "contact_id";
        $params['value'] = $organization->getBexioId();
        $postData[] = $params;

        $postData = json_encode($postData);

        $url = $this->createUrl('/kb_invoice/search');
        $signature = $this->createSignature(self::METHOD_POST, $url, $postData);

        return $this->curlCall(self::METHOD_POST, $url, $signature, $postData);
    }

    public function showInvoicePdf($id)
    {
        $url = $this->createUrl('/kb_invoice/'.$id.'/pdf');
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    public function createInvoice($organization, $calculatedData)
    {
        if (!$organization->getBexioId()) {
            return false;
        }

//        $taxes = $this->listTaxes();
//        $articles = $this->listItems();
//        $positions = $this->listDefaultPositions(12);

        $positions = [];

        $positionBase['type'] = 'KbPositionCustom';
        $positionBase['unit_price'] = $calculatedData['base_usage_price'];
        $positionBase['text'] = $calculatedData['base_usage_text'];
        $positionBase['tax_id'] = 16;
        $positionBase['amount'] = 1;

        $positions[] = $positionBase;

        if ($calculatedData['additional_usage_gb'] > 0) {
            $positionBase['type'] = 'KbPositionCustom';
            $positionAdditional['unit_price'] = $calculatedData['additional_gb_price'];
            $positionAdditional['text'] = $calculatedData['additional_usage_text'];
            $positionAdditional['tax_id'] = 16;
            $positionAdditional['amount'] = $calculatedData['additional_usage_gb'];

            $positions[] = $positionAdditional;
        }

        $params['user_id'] = $this->userId;
        $params['contact_id'] = $organization->getBexioId();
        $params['positions'] = $positions;
        $params['mwst_type'] = 0;
        $params['mwst_is_net'] = true;

        $postData = json_encode($params);

        $url = $this->createUrl('/kb_invoice');
        $signature = $this->createSignature(self::METHOD_POST, $url, $postData);

        return $this->curlCall(self::METHOD_POST, $url, $signature, $postData);
    }

    private function listItems()
    {
        $url = $this->createUrl('/article');
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    private function listTaxes()
    {
        $url = $this->createUrl('/tax');
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    private function listDefaultPositions($id)
    {
        $url = $this->createUrl('/kb_invoice/'.$id.'/kb_position_custom');
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    private function showInvoice($invoice)
    {
        $url = $this->createUrl('/kb_invoice/'.$invoice->id);
        $signature = $this->createSignature(self::METHOD_GET, $url);

        return $this->curlCall(self::METHOD_GET, $url, $signature);
    }

    public function createPayment($invoice, $sum)
    {
        $params['value'] = $sum;

        $postData = json_encode($params);

        $url = $this->createUrl('/kb_invoice/'.$invoice->id.'/payment');
        $signature = $this->createSignature(self::METHOD_POST, $url, $postData);

        return $this->curlCall(self::METHOD_POST, $url, $signature, $postData);
    }

    public function sendInvoice($organization, $invoice)
    {
        if (!$organization->getBexioId()) {
            return false;
        }

        $user = $organization->getFirstUser();

        $message = $this->templating->render('emails/invoiceBexio.html.twig', ['user' => $user]);

        $params['recipient_email'] = $user->getEmail();
        $params['subject'] = $this->translator->trans('emailInvoiceBexio.subject');
        $params['message'] = $message;
        $params['mark_as_open'] = true;
        $params['sender'] = $this->from;

        $postData = json_encode($params);

        $url = $this->createUrl('/kb_invoice/'.$invoice->id.'/send');
        $signature = $this->createSignature(self::METHOD_POST, $url, $postData);

        $result = $this->curlCall(self::METHOD_POST, $url, $signature, $postData);
        if ($result) {
            return $result->success;
        } else {
            return false;
        }
    }
}