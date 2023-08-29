<?php

namespace App\Form;

use App\Constraint\PasswordConstraint;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('username', TextType::class, [
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[^0-9].*$/',
                        'message' => 'Username cannot start with a number'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-z0-9_]+$/',
                        'message' => 'Username can only consist of letters, numbers and the underscore'
                    ]),
                    new Length([
                        'min' => 6,
                        'max' => 32,
                        'minMessage' => 'Your username must be at least {{ limit }} characters long',
                        'maxMessage' => 'Your username is too long'
                    ]),
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Akceptuje regulamin',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our rules.',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The given passwords are different',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'This field cannot be empty'
                    ]),
                    new Length([
                        'min' => 12,
                        'max' => 40,
                        'minMessage' => 'Your password must be at least {{ limit }} characters long',
                        'maxMessage' => 'Your password is too long'
                    ]),
                    new PasswordConstraint()
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'constraints' => [
                new UniqueEntity([
                    'entityClass' => User::class,
                    'fields' => 'username',
                    'message' => 'User with this username already exists'
                ])
            ]
        ]);
    }
}
