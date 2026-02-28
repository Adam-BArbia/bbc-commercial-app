<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code article',
                'attr' => [
                    'placeholder' => 'Ex: ART001',
                    'maxlength' => 50,
                ],
                'help' => 'Code unique de l\'article (max 50 caractères)',
            ])
            ->add('designation', TextType::class, [
                'label' => 'Désignation',
                'attr' => [
                    'placeholder' => 'Nom ou description de l\'article',
                    'maxlength' => 255,
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Article actif',
                'required' => false,
                'help' => 'Décocher pour désactiver l\'article',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
