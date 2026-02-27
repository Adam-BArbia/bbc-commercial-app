<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Find active articles
     */
    public function findActiveArticles()
    {
        return $this->createQueryBuilder('a')
            ->where('a.active = :active')
            ->setParameter('active', true)
            ->orderBy('a.designation', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
