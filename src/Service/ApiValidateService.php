<?php

namespace App\Service;


use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiValidateService
{
    private $validator;
    private $translator;
    private $response;

    public function __construct(ValidatorInterface $validator, TranslatorInterface $translator)
    {
        $this->validator = $validator;
        $this->translator = $translator;
        $this->response = null;
    }

    public function checkDataRegisterInfrastructureValidate(array $fields, array $data)
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || array_key_exists($field, $data) && $data[$field] == '') {
                $this->response['errors'][] = $this->translator->trans('apiErrors.missingField', ['%field%' => $field]);
            } else {
                if ($field == 'url') {
                    $violations = $this->validator->validate($data[$field],[
                        new Url(['message' => $this->translator->trans('apiErrors.notValid', ['%field%' => 'url'])]),
                    ]);

                    if (0 !== count($violations->findByCodes(Url::INVALID_URL_ERROR))) {
                        $this->response['errors'][] = $violations[0]->getMessage();
                    }
                }
            }
        }

        return $this->response;
    }
}