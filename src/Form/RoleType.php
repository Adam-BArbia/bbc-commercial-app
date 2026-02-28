<?php

namespace App\Form;

use App\Entity\Privilege;
use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du rôle (ex: ROLE_MANAGER)',
            ])
            ->add('privileges', EntityType::class, [
                'class' => Privilege::class,
                'choice_label' => fn (Privilege $privilege) => sprintf('%s - %s', $privilege->getCode(), $privilege->getDescription()),
                'label' => 'Privilèges',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}
