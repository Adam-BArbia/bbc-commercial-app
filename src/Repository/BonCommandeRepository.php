<?php

namespace App\Repository;

use App\Entity\BonCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonCommande>
 */
class BonCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonCommande::class);
    }

    /**
     * Find orders that can be edited (no deliveries)
     */
    public function findEditable()
    {
        return $this->createQueryBuilder('bc')
            ->where('bc.status != :cancelled')
            ->setParameter('cancelled', 'CANCELLED')
            ->getQuery()
            ->getResult();
    }
}
