<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client_code', TextType::class, [
                'label' => 'Code client',
                'attr' => [
                    'placeholder' => 'Ex: CLI001',
                    'maxlength' => 50,
                ],
                'help' => 'Code unique du client (max 50 caractères)',
            ])
            ->add('matricule_fiscale', TextType::class, [
                'label' => 'Matricule fiscal',
                'attr' => [
                    'placeholder' => 'Ex: 1234567A',
                    'maxlength' => 50,
                ],
                'help' => 'Matricule fiscal unique (max 50 caractères)',
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom du client',
                'attr' => [
                    'placeholder' => 'Raison sociale ou nom complet',
                    'maxlength' => 255,
                ],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'placeholder' => 'Adresse complète du client',
                    'rows' => 4,
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Client actif',
                'required' => false,
                'help' => 'Décocher pour désactiver le client',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
