<?php
namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class DetailsType extends UserBaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name', TextType::class, [
                'label' => 'profileDetails.organization_name',
                'attr' => [
                    'placeholder' => 'profileDetails.organization_name',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.not_blank']),
                    new Regex([
                        'pattern' => "/^(.){3,50}$/",
                        'message' => 'profileDetails.organization_name'
                    ]),
                ]
            ])
            ->add('address1', TextType::class, [
                'label' => 'profileDetails.organization_address1',
                'attr' => [
                    'placeholder' => 'profileDetails.organization_address1',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.not_blank']),
                    new Regex([
                    	'pattern' => "/^(.){3,50}$/",
                        'message' => 'profileDetails.organization_address1'
                    ]),
                ]
            ])
            ->add('address2', TextType::class, [
                'label' => 'profileDetails.organization_address2',
                'attr' => [
                    'placeholder' => 'profileDetails.organization_address2',
                ],
                'required' => false,
                'constraints' => [
                    new Regex([
                    	'pattern' => "/^(.){3,50}$/",
                        'message' => 'profileDetails.organization_address2'
                    ]),
                ]
            ])
            ->add('address3', TextType::class, [
                'label' => 'profileDetails.organization_address3',
                'attr' => [
                    'placeholder' => 'profileDetails.organization_address3',
                ],
                'required' => false,
                'constraints' => [
                    new Regex([
                    	'pattern' => "/^(.){3,50}$/",
                        'message' => 'profileDetails.organization_address3'
                    ]),
                ]
            ])
            ->add('zip', TextType::class, [
                'label' => 'profileDetails.organization_zip',
                'attr' => [
                    'placeholder' => 'profileDetails.organization_zip',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.not_blank']),
                    new Regex([
                        'pattern' => "/^[a-zA-Z0-9]{4,10}$/",
                        'message' => 'profileDetails.organization_zip'
                    ]),
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'profileDetails.organization_city',
                'attr' => [
                    'placeholder' => 'profileDetails.organization_city',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.not_blank']),
                    new Regex([
                    	'pattern' => "/^(.){2,50}$/",
                        'message' => 'profileDetails.organization_city'
                    ]),
                ]
            ])
            ->add('country',EntityType::class, [
                'label' => 'profileDetails.organization_country',
                'attr' => [
                    'placeholder' => 'profileDetails.organization_country',
                ],
                'class'         => 'App\Entity\Country',
                'choice_label'  => 'name',
                'constraints' => [
                    new NotBlank(['message' => 'profileDetails.organization_country']),
                ]
            ])
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'profileDetails.password_identical',
                'first_options'  => array('label' => 'profileDetails.password', 'attr' => ['placeholder' => 'profileDetails.password']),
                'second_options' => array('label' => 'profileDetails.repeat_password', 'attr' => ['placeholder' => 'profileDetails.repeat_password']),
            ))
            ->add('updatePassword', CheckboxType::class, [
                'label' => 'profileDetails.update_password'
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }

    public function onPreSubmit(FormEvent $event)
    {
        if ($event->getData() != null) {
            /** @var FormBuilderInterface $form */
            $form = $event->getForm();

            if (array_key_exists('updatePassword', $event->getData())) {
                $form->add('plainPassword', RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'invalid_message' => 'profileDetails.password_identical',
                    'first_options'  => array('label' => 'profileDetails.password', 'attr' => ['placeholder' => 'profileDetails.password']),
                    'second_options' => array('label' => 'profileDetails.repeat_password', 'attr' => ['placeholder' => 'profileDetails.repeat_password']),
                    'constraints' => array(
                        new NotBlank(['message' => 'profileDetails.not_blank']),
                        new Length([
                            'min' => "8",
                            'minMessage' => 'profileDetails.password_min',
                            'max' => '4096',
                            'maxMessage' => 'profileDetails.password_max'
                        ]),
                        new Regex([
                            'pattern' => "/[A-Z]{1}/",
                            'message' => "profileDetails.password_uppercase"
                        ]),
                        new Regex([
                            'pattern' => "/[0-9]{1}/",
                            'message' => "profileDetails.password_number"
                        ])
                    )
                ));
            }
        }
    }
}