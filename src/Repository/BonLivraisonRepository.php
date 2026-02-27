<?php

namespace App\Repository;

use App\Entity\BonLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonLivraison>
 */
class BonLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonLivraison::class);
    }

    /**
     * Find delivery notes not yet invoiced
     */
    public function findNotInvoiced()
    {
        return $this->createQueryBuilder('bl')
            ->where('bl.facture IS NULL')
            ->andWhere('bl.status != :cancelled')
            ->setParameter('cancelled', 'CANCELLED')
            ->orderBy('bl.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
