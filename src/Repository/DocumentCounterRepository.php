<?php

namespace App\Repository;

use App\Entity\DocumentCounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentCounter>
 */
class DocumentCounterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentCounter::class);
    }

    /**
     * Get or create document counter for a type and year
     */
    public function getOrCreateCounter(string $documentType, int $year): DocumentCounter
    {
        $counter = $this->findOneBy([
            'document_type' => $documentType,
            'year' => $year,
        ]);

        if (!$counter) {
            $counter = new DocumentCounter();
            $counter->setDocumentType($documentType);
            $counter->setYear($year);
            $counter->setLastNumber(0);
            $this->getEntityManager()->persist($counter);
        }

        return $counter;
    }
}
