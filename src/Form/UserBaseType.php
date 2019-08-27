<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

abstract class UserBaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'profileDetails.user_email',
                'attr' => [
                    'placeholder' => 'profileDetails.user_email',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.not_blank']),
                    new Regex([
                        'pattern' => "/^[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,64}$/",
                        'message' => 'profileDetails.user_email'
                    ]),
                ]
            ])
            ->add('firstname', TextType::class, [
                'label' => 'profileDetails.user_firstname',
                'attr' => [
                    'placeholder' => 'profileDetails.user_firstname',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.not_blank']),
                    new Regex([
                    	'pattern' => "/^(.){2,50}$/",
                        'message' => 'profileDetails.user_firstname'
                    ]),
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => 'profileDetails.user_lastname',
                'attr' => [
                    'placeholder' => 'profileDetails.user_lastname',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.not_blank']),
                    new Regex([
                    	'pattern' => "/^(.){2,50}$/",
                        'message' => 'profileDetails.user_lastname'
                    ]),
                ]
            ])
        ;
    }
}