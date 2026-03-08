<?php

namespace App\Form;

use App\Entity\BonCommandeItem;
use App\Entity\BonLivraisonItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BonLivraisonItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $bonCommandeItems = $options['bon_commande_items'] ?? null;

        $builder
            ->add('bon_commande_item', EntityType::class, [
                'class' => BonCommandeItem::class,
                'choices' => $bonCommandeItems ?: [],
                'choice_label' => fn(BonCommandeItem $item) => sprintf(
                    '%s - Qty: %s (Remaining: %s)',
                    $item->getArticle()?->getDesignation() ?? 'Unknown',
                    (string)$item->getQuantity(),
                    (string)($item->getRemainingQuantity() ?? $item->getQuantity())
                ),
                'disabled' => true,
                'label' => 'Article de commande',
                'required' => true,
            ])
            ->add('quantity_delivered', null, [
                'label' => 'Quantité livrée',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'placeholder' => '0.00',
                    'step' => '0.01',
                    'class' => 'quantity-delivered-input',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(['message' => 'La quantité doit être positive']),
                ],
            ]);

        // Validate that delivered quantity doesn't exceed remaining quantity
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!$data instanceof BonLivraisonItem) {
                return;
            }

            $orderItem = $data->getBonCommandeItem();
            if (!$orderItem) {
                return;
            }

            $deliveredQty = (float)$data->getQuantityDelivered();
            $remainingQty = (float)($orderItem->getRemainingQuantity() ?? $orderItem->getQuantity());

            if ($deliveredQty > $remainingQty) {
                $form = $event->getForm();
                $form->get('quantity_delivered')->addError(
                    new \Symfony\Component\Form\FormError(
                        sprintf(
                            'La quantité livrée (%.2f) ne peut pas dépasser la quantité restante (%.2f)',
                            $deliveredQty,
                            $remainingQty
                        )
                    )
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => BonLivraisonItem::class,
                'bon_commande_items' => null,
            ])
            ->setAllowedTypes('bon_commande_items', ['null', 'array']);
    }
}
