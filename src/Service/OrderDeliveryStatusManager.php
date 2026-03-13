<?php

namespace App\Service;

use App\Entity\BonCommande;

class OrderDeliveryStatusManager
{
    public function refresh(?BonCommande $bonCommande): bool
    {
        if ($bonCommande === null || $bonCommande->getStatus() === 'CANCELLED') {
            return false;
        }

        $previousStatus = $bonCommande->getStatus();
        $hasDeliveredQuantity = false;
        $isFullyDelivered = $bonCommande->getBonCommandeItems()->count() > 0;

        foreach ($bonCommande->getBonCommandeItems() as $orderItem) {
            $delivered = (float) $orderItem->getDeliveredQuantity();
            $ordered = (float) $orderItem->getQuantity();

            if ($delivered > 0.0001) {
                $hasDeliveredQuantity = true;
            }

            if ($ordered <= 0.0001) {
                continue;
            }

            if ($delivered + 0.0001 < $ordered) {
                $isFullyDelivered = false;
            }
        }

        if ($isFullyDelivered && $hasDeliveredQuantity) {
            $bonCommande->setStatus('DELIVERED');
        } elseif ($hasDeliveredQuantity) {
            $bonCommande->setStatus('PARTIALLY_DELIVERED');
        } elseif (in_array($bonCommande->getStatus(), ['PARTIALLY_DELIVERED', 'DELIVERED'], true)) {
            $bonCommande->setStatus('CONFIRMED');
        }

        return $previousStatus !== $bonCommande->getStatus();
    }
}