<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Find payments in a date range
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->createQueryBuilder('p')
            ->where('p.payment_date >= :start')
            ->andWhere('p.payment_date <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('p.payment_date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
