<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\StorageObject;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\StorageObjectRepository;
use App\Service\StorageService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\DetailsType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\PaymentService;
use App\Service\AccountingService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use ZipStream\ZipStream;

class BaseController extends AbstractController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(): Response
    {
        return $this->render('homepage/homepage.html.twig', []);
    }

    /**
     * @Route("/bucket", name="app_bucket")
     * @param StorageService $storageService
     * @return Response
     */
    public function bucket(StorageService $storageService): Response
    {
        /** @var Organization $organization */
        $organization = $this->getUser()->getFirstOrganization();
        $storageObject = $organization->getFirstStorageObject();

        $bucket = $storageService->getListObjects($storageObject);

        return $this->render('bucket/bucket.html.twig', [
            'bucket' => $bucket,
            'organization' => $organization,
            'bucketId' => $storageObject->getBucketId()
        ]);
    }

    /**
     * @Route("/bucket/download", name="app_bucket_download")
     * @Method("POST")
     * @param Request $request
     * @param StorageService $storageService
     * @param StorageObjectRepository $storageObjectRepository
     * @return RedirectResponse|Response
     */
    public function bucketDownload(Request $request, StorageService $storageService, StorageObjectRepository $storageObjectRepository)
    {
        /** @var User $user */
        $user = $this->getUser();
        $bucketId = $request->request->get('bucketId');
        $objects = json_decode($request->request->get('objects'), true);

        if ($user->isAuthenticationUser() && $bucketId && $objects) {
            $so = $storageObjectRepository->findOneBy(['bucketId' => $bucketId]);

            //Check for existence
            foreach ($objects as $key => $object) {
                if (!$storageService->doesObjectExist($so, $bucketId, $object)) {
                    unset($objects[$key]);
                }
            }
            // If more one object return zip
            if (!empty($objects) && count($objects) > 1) {
                $storageService->registerStreamWrapper($so);

                return new StreamedResponse(function() use($objects, $bucketId) {
                    $zipName = (new \DateTime())->format('Y-m-d_H:i:s') . '-' . $bucketId . '.zip';
                    $zip = new ZipStream($zipName, ['content_type' => 'application/octet-stream']);

                    foreach ($objects as $object) {
                        $s3path = "s3://" . $bucketId . "/" . $object;
                        if ($streamRead = fopen($s3path, 'r')) {
                            $zip->addFileFromStream(basename($object), $streamRead);
                        } else {
                            $this->addFlash('error','message.error_bucket_create_zip');
                        }
                    }

                    $zip->finish();
                });
                // If one file return file
            } elseif (!empty($objects)) {
                $file = $storageService->getObject($so, $bucketId, $objects[0]);

                if ($file) {
                    $position = strrpos($objects[0], '/') ? strrpos($objects[0], '/') + 1 : 0;

                    $response = new Response($file['Body']);
                    $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, substr($objects[0], $position));
                    $response->headers->set('Content-Disposition', $disposition);
                    $response->headers->set('Content-type', $file['ContentType']);
                    $response->headers->set('Content-length', $file['ContentLength']);

                    return $response;
                }

                $this->addFlash('error','message.error_bucket_no_file');
            } else {
                $this->addFlash('error','message.error_bucket_not_files_exist');
            }
        } else {
            $this->addFlash('error','message.error_bucket_no_all_data');
        }

        return $this->redirectToRoute('app_bucket');
    }

    /**
     * @Route("/profile", name="app_profile")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param AccountingService $accountingService
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function profile(Request $request, UserPasswordEncoderInterface $passwordEncoder, AccountingService $accountingService, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $organization = $user->getFirstOrganization();
        $checkPasswordUpdate = true;

        $form = $this->createForm(DetailsType::class)
            ->remove('email')
            ->remove('name');

        $form->get('address1')->setData($organization->getAddress1());
        $form->get('address2')->setData($organization->getAddress2());
        $form->get('address3')->setData($organization->getAddress3());
        $form->get('zip')->setData($organization->getZip());
        $form->get('city')->setData($organization->getCity());
        $form->get('country')->setData($organization->getCountry());
        $form->get('firstname')->setData($user->getFirstname());
        $form->get('lastname')->setData($user->getLastname());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);

            if (isset($data['plainPassword'])) {
                $password = $passwordEncoder->encodePassword($user, $data['plainPassword']);
                $user->setPassword($password);
                $checkPasswordUpdate = false;
            }
            $em->persist($user);

            $organization->setAddress1($data['address1']);
            $organization->setAddress2($data['address2']);
            $organization->setAddress3($data['address3']);
            $organization->setZip($data['zip']);
            $organization->setCity($data['city']);
            $organization->setCountry($data['country']);
            $em->persist($organization);
            $em->flush();

            $this->addFlash('notice', 'message.your_changes_were_saved');

            //update Bexio contact
            if ($organization->getBexioId()) {
                $bexioId = $accountingService->updateContact($organization);
                if (!$bexioId) {
                    $this->addFlash('error','message.error_during_updating_contact_bexio');
                }
            }
        }

        return $this->render('profile/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'organization' => $organization,
            'checkPasswordUpdate' => $checkPasswordUpdate
        ]);
    }

    /**
     * @Route("/payment-settings", name="app_payment_settings")
     */
    public function paymentSettings(Request $request, PaymentService $paymentService, Accountingservice $accountingService)
    {
        $user = $this->getUser();
        $organization = $user->getFirstOrganization();
        $card = false;

        if ($request->isMethod('POST')) {
            $em = $this->getDoctrine()->getManager();
            $token = $request->request->get('token');

            if ($organization->getCcToken()) {
                //Stripe customer is already created

                $card = $paymentService->addCardToCustomer($organization, $token);
                if (!$card) {
                    $this->addFlash('error', 'message.error_during_adding_new_card');
                } else {
                    $this->addFlash('notice', 'message.your_changes_were_saved');
                }
            } else {
                //there is no Stripe customer yet
                $customer = $paymentService->createCustomerWithCard($user, $token);
                if ($customer) {
                    $organization->setCcToken($customer->id);
                    $organization->setTrialEndsAt(null);
                    $billingDueDate = new \DateTime('now +30 day');
                    $organization->setBillingDueDate($billingDueDate);
                    $em->persist($organization);
                    $em->flush();

                    $this->addFlash('notice','message.your_changes_were_saved');

                    $card = $paymentService->getCustomerLastCard($organization);
                    if (!$card) {
                        $this->addFlash('error','message.error_during_retreive_customer');
                    }

                    //create Bexio contact
                    $bexioId = $accountingService->createContact($organization);
                    if ($bexioId) {
                        $organization->setBexioId($bexioId);
                        $em->persist($organization);
                        $em->flush();
                    } else {
                        $this->addFlash('error','message.error_during_adding_new_contact_bexio');
                    }
                } else {
                    $this->addFlash('error','message.error_during_adding_new_customer');
                }
            }
        } else {
            if ($organization->getCcToken()) {
                $card = $paymentService->getCustomerLastCard($organization);
                if (!$card) {
                    $this->addFlash('error','message.error_during_retreive_customer');
                }
            }
        }

        return $this->render('payment/paymentSettings.html.twig', [
            'organization' => $organization,
            'card' => $card
        ]);
    }

    /**
     * @Route("/invoices", name="app_invoices")
     */
    public function invoices(): Response
    {
        $user = $this->getUser();
        $organization = $user->getFirstOrganization();

        return $this->render('invoices/invoices.html.twig', [
            'organization' => $organization
        ]);
    }

    /**
     * @Route("/invoices-ajax", name="app_invoices_ajax")
     */
    public function invoicesAjax(Request $request, AccountingService $accountingService): Response
    {
        $user = $this->getUser();
        $organization = $user->getFirstOrganization();
        $invoices = $accountingService->getContactInvoices($organization);

        if ($invoices) {
            $data = [
                'draw' => $request->get('draw', 0) + 1,
                'recordsTotal' => count($invoices),
                'recordsFiltered' => count($invoices),
                'data' => $invoices
            ];
        } else {
            $data = [
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ];
        }

        return new Response(json_encode($data));
    }

    /**
     * @Route("/invoices/{id}/pdf", name="app_invoice_download")
     */
    public function invoiceDownload(Request $request, $id, AccountingService $accountingService): Response
    {
        $pdf = $accountingService->showInvoicePdf($id);

        if ($pdf) {
            $response = new Response(base64_decode($pdf->content));
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $pdf->name);
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-type', $pdf->mime);
            $response->headers->set('Content-length', $pdf->size);
            return $response;
        } else {
            $this->addFlash('error','message.error_during_retreive_invoice_bexio');
            return $this->redirectToRoute('app_invoices');
        }
    }

    /**
     * @param $organizationRepository
     * @return Response
     * @throws \Exception
     */
    public function trialPeriodBanner(OrganizationRepository $organizationRepository): Response
    {
        $params = [];
        if ($this->getUser()) {
            $params['organization'] = $this->getUser()->getFirstOrganization();
            $dateNow = (new \DateTime('now'))->format('Y-m-d');
            $trialEndsAt = $params['organization']->getTrialEndsAt();

            if ($trialEndsAt && $trialEndsAt->format('Y-m-d') >= $dateNow) {
                $params['days'] = $organizationRepository->getDaysTrialPeriodBanner($trialEndsAt, $dateNow);
            }
        }

        return $this->render('includes/trialPeriodBanner.html.twig', $params);
    }
}