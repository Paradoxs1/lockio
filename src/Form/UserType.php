<?php
namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends UserBaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('plainPassword', RepeatedType::class, array(
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
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}