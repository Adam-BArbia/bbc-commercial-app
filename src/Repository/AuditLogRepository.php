<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * Find logs by table name
     */
    public function findByTableName(string $tableName)
    {
        return $this->createQueryBuilder('al')
            ->where('al.table_name = :tableName')
            ->setParameter('tableName', $tableName)
            ->orderBy('al.performed_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by user
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('al')
            ->where('al.performed_by = :user')
            ->setParameter('user', $user)
            ->orderBy('al.performed_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
