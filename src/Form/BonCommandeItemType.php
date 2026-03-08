<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\BonCommandeItem;
use App\Repository\ArticleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BonCommandeItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('article', EntityType::class, [
                'class' => Article::class,
                'choice_label' => fn (Article $article) => sprintf('%s - %s', $article->getCode(), $article->getDesignation()),
                'placeholder' => 'Selectionner un article',
                'label' => 'Article',
                'query_builder' => fn (ArticleRepository $repository) => $repository->createQueryBuilder('a')
                    ->andWhere('a.active = :active')
                    ->setParameter('active', true)
                    ->orderBy('a.designation', 'ASC'),
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantite',
                'scale' => 2,
                'attr' => [
                    'step' => '0.01',
                    'min' => '0.01',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('unit_price_snapshot', NumberType::class, [
                'label' => 'Prix unitaire (TND)',
                'scale' => 3,
                'attr' => [
                    'step' => '0.001',
                    'min' => '0',
                    'placeholder' => '0.000',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonCommandeItem::class,
        ]);
    }
}
