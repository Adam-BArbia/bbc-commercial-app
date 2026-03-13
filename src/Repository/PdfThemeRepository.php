<?php

namespace App\Repository;

use App\Entity\PdfTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PdfTheme>
 */
class PdfThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PdfTheme::class);
    }

    public function findActiveByType(string $documentType): ?PdfTheme
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.documentType = :documentType OR t.documentType = :both')
            ->andWhere('t.isActive = :isActive')
            ->setParameter('documentType', $documentType)
            ->setParameter('both', PdfTheme::TYPE_BOTH)
            ->setParameter('isActive', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deactivateTypeThemes(string $documentType, ?int $excludedId = null): void
    {
        // A BOTH theme serves all types; a specific type is also covered by BOTH themes.
        // Deactivate every theme that overlaps with $documentType.
        $typesToDeactivate = $documentType === PdfTheme::TYPE_BOTH
            ? [PdfTheme::TYPE_DELIVERY, PdfTheme::TYPE_INVOICE, PdfTheme::TYPE_BOTH]
            : [$documentType, PdfTheme::TYPE_BOTH];

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->update(PdfTheme::class, 't')
            ->set('t.isActive', ':inactive')
            ->where('t.documentType IN (:types)')
            ->setParameter('inactive', false)
            ->setParameter('types', $typesToDeactivate);

        if ($excludedId !== null) {
            $qb
                ->andWhere('t.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        $qb->getQuery()->execute();
    }
}
