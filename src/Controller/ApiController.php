<?php

namespace App\Controller;

use App\Entity\StorageObject;
use App\Service\ApiValidateService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/register-infra", name="registerInfrastructure")
     * @Method("POST")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param ValidatorInterface $validator
     * @param ApiValidateService $apiValidateService
     * @return Response
     */
    public function registerInfrastructure(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, ValidatorInterface $validator, ApiValidateService $apiValidateService)
    {
        $xAuthToken = $request->headers->get('X-AUTH-TOKEN');
        $response = null;
        $status = Response::HTTP_FORBIDDEN;

        if ($xAuthToken == $this->getParameter('register_infra_auth_token')) {
            $data = json_decode($request->getContent(), true);
            $fields = ['url', 'access_key', 'secret_key'];

            $response = $apiValidateService->checkDataRegisterInfrastructureValidate($fields, $data);

            if (empty($response['errors'])) {
                $storageObject = new StorageObject();
                $storageObject->setUrl($data['url'])
                    ->setAccessKey($data['access_key'])
                    ->setSecretKey($data['secret_key']);

                $em->persist($storageObject);
                $em->flush();

                $status = Response::HTTP_NO_CONTENT;
            } else {
                return new JsonResponse($response,  Response::HTTP_BAD_REQUEST);
            }
        } else {
            $response['errors'] = $translator->trans('apiErrors.accessDenied');
        }

        return new JsonResponse($response, $status);
    }

}