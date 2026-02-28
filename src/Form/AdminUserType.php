<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordRequired = (bool) $options['password_required'];
        $passwordConstraints = [
            new Regex(
                pattern: '/^$|.{6,}$/',
                message: 'Le mot de passe doit contenir au moins 6 caractères.'
            ),
        ];

        if ($passwordRequired) {
            array_unshift($passwordConstraints, new NotBlank(message: 'Le mot de passe est obligatoire.'));
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
            ])
            ->add('role', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'name',
                'label' => 'Rôle',
                'placeholder' => 'Sélectionner un rôle',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Utilisateur actif',
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $passwordRequired ? 'Mot de passe' : 'Nouveau mot de passe (optionnel)',
                'mapped' => false,
                'required' => $passwordRequired,
                'empty_data' => '',
                'constraints' => $passwordConstraints,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_required' => false,
        ]);

        $resolver->setAllowedTypes('password_required', 'bool');
    }
}
