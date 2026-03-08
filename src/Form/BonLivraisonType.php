<?php

namespace App\Form;

use App\Entity\BonCommande;
use App\Entity\BonLivraison;
use App\Repository\BonCommandeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BonLivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bon_commande', EntityType::class, [
                'class' => BonCommande::class,
                'choice_label' => function(BonCommande $order) {
                    return sprintf('%s - %s', $order->getReference(), $order->getClient()?->getName() ?? 'Unknown');
                },
                'query_builder' => function(BonCommandeRepository $repo) {
                    return $repo->createQueryBuilder('bc')
                        ->leftJoin('bc.client', 'c')
                        ->where('bc.status != :cancelled')
                        ->setParameter('cancelled', 'CANCELLED')
                        ->orderBy('bc.reference', 'DESC');
                },
                'label' => 'Bon de commande',
                'placeholder' => '--- Sélectionner une commande ---',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonLivraison::class,
        ]);
    }
}
