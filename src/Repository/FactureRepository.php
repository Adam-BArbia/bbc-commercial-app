<?php

namespace App\Repository;

use App\Entity\Facture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    /**
     * Find unpaid or partially paid invoices
     */
    public function findUnpaidInvoices()
    {
        return $this->createQueryBuilder('f')
            ->where('f.status IN (:statuses)')
            ->setParameter('statuses', ['UNPAID', 'PARTIALLY_PAID'])
            ->orderBy('f.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
