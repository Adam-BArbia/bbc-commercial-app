<?php

namespace App\Form;

use App\Entity\BonCommande;
use App\Entity\Client;
use App\Repository\ClientRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class BonCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => fn (Client $client) => sprintf('%s - %s', $client->getClientCode(), $client->getName()),
                'placeholder' => 'Selectionner un client',
                'label' => 'Client',
                'query_builder' => fn (ClientRepository $repository) => $repository->createQueryBuilder('c')
                    ->andWhere('c.active = :active')
                    ->setParameter('active', true)
                    ->orderBy('c.name', 'ASC'),
            ])
            ->add('bonCommandeItems', CollectionType::class, [
                'entry_type' => BonCommandeItemType::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'Ajoutez au moins une ligne a la commande.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonCommande::class,
        ]);
    }
}
