<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReportController extends AbstractController
{
    #[Route('/reports', name: 'app_reports_index')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $today = new \DateTimeImmutable('today');

        $dateFrom = $this->parseDateInput((string) $request->query->get('date_from'), $today->modify('-29 days'));
        $dateTo = $this->parseDateInput((string) $request->query->get('date_to'), $today);

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $fromDateTime = $dateFrom->setTime(0, 0, 0);
        $toDateTime = $dateTo->setTime(23, 59, 59);

        $ordersCount = (int) $entityManager->createQueryBuilder()
            ->select('COUNT(bc.id)')
            ->from('App\\Entity\\BonCommande', 'bc')
            ->where('bc.created_at BETWEEN :from AND :to')
            ->setParameter('from', $fromDateTime)
            ->setParameter('to', $toDateTime)
            ->getQuery()
            ->getSingleScalarResult();

        $deliveriesCount = (int) $entityManager->createQueryBuilder()
            ->select('COUNT(bl.id)')
            ->from('App\\Entity\\BonLivraison', 'bl')
            ->where('bl.created_at BETWEEN :from AND :to')
            ->setParameter('from', $fromDateTime)
            ->setParameter('to', $toDateTime)
            ->getQuery()
            ->getSingleScalarResult();

        $invoicesCount = (int) $entityManager->createQueryBuilder()
            ->select('COUNT(f.id)')
            ->from('App\\Entity\\Facture', 'f')
            ->where('f.created_at BETWEEN :from AND :to')
            ->setParameter('from', $fromDateTime)
            ->setParameter('to', $toDateTime)
            ->getQuery()
            ->getSingleScalarResult();

        $paymentsCount = (int) $entityManager->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from('App\\Entity\\Payment', 'p')
            ->where('p.payment_date BETWEEN :fromDate AND :toDate')
            ->setParameter('fromDate', $dateFrom)
            ->setParameter('toDate', $dateTo)
            ->getQuery()
            ->getSingleScalarResult();

        $totalBilled = (float) $entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(f.total_ttc), 0)')
            ->from('App\\Entity\\Facture', 'f')
            ->where('f.created_at BETWEEN :from AND :to')
            ->andWhere('f.status != :cancelled')
            ->setParameter('from', $fromDateTime)
            ->setParameter('to', $toDateTime)
            ->setParameter('cancelled', 'CANCELLED')
            ->getQuery()
            ->getSingleScalarResult();

        $totalCollected = (float) $entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(p.amount), 0)')
            ->from('App\\Entity\\Payment', 'p')
            ->where('p.payment_date BETWEEN :fromDate AND :toDate')
            ->setParameter('fromDate', $dateFrom)
            ->setParameter('toDate', $dateTo)
            ->getQuery()
            ->getSingleScalarResult();

        $outstanding = max(0, $totalBilled - $totalCollected);
        $collectionRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0.0;

        $statusRows = $entityManager->createQueryBuilder()
            ->select('f.status AS status, COUNT(f.id) AS count')
            ->from('App\\Entity\\Facture', 'f')
            ->where('f.created_at BETWEEN :from AND :to')
            ->setParameter('from', $fromDateTime)
            ->setParameter('to', $toDateTime)
            ->groupBy('f.status')
            ->getQuery()
            ->getArrayResult();

        $invoiceStatus = [
            'UNPAID' => 0,
            'PARTIALLY_PAID' => 0,
            'PAID' => 0,
            'CANCELLED' => 0,
        ];

        foreach ($statusRows as $row) {
            $status = (string) ($row['status'] ?? '');
            if (array_key_exists($status, $invoiceStatus)) {
                $invoiceStatus[$status] = (int) $row['count'];
            }
        }

        $topClients = $entityManager->createQueryBuilder()
            ->select('c.name AS clientName, COUNT(DISTINCT bc.id) AS ordersCount, COALESCE(SUM(bci.quantity * bci.unit_price_snapshot), 0) AS totalOrdered')
            ->from('App\\Entity\\BonCommande', 'bc')
            ->join('bc.client', 'c')
            ->join('bc.bonCommandeItems', 'bci')
            ->where('bc.created_at BETWEEN :from AND :to')
            ->andWhere('bc.status != :cancelled')
            ->setParameter('from', $fromDateTime)
            ->setParameter('to', $toDateTime)
            ->setParameter('cancelled', 'CANCELLED')
            ->groupBy('c.id, c.name')
            ->orderBy('totalOrdered', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getArrayResult();

        $topArticles = $entityManager->createQueryBuilder()
            ->select('a.designation AS articleName, COALESCE(SUM(bci.quantity), 0) AS totalQuantity, COALESCE(SUM(bci.quantity * bci.unit_price_snapshot), 0) AS totalValue')
            ->from('App\\Entity\\BonCommandeItem', 'bci')
            ->join('bci.article', 'a')
            ->join('bci.bon_commande', 'bc')
            ->where('bc.created_at BETWEEN :from AND :to')
            ->andWhere('bc.status != :cancelled')
            ->setParameter('from', $fromDateTime)
            ->setParameter('to', $toDateTime)
            ->setParameter('cancelled', 'CANCELLED')
            ->groupBy('a.id, a.designation')
            ->orderBy('totalValue', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getArrayResult();

        return $this->render('reports/index.html.twig', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'ordersCount' => $ordersCount,
            'deliveriesCount' => $deliveriesCount,
            'invoicesCount' => $invoicesCount,
            'paymentsCount' => $paymentsCount,
            'totalBilled' => $totalBilled,
            'totalCollected' => $totalCollected,
            'outstanding' => $outstanding,
            'collectionRate' => $collectionRate,
            'invoiceStatus' => $invoiceStatus,
            'topClients' => $topClients,
            'topArticles' => $topArticles,
        ]);
    }

    private function parseDateInput(string $input, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        if ($input === '') {
            return $fallback;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $input);

        return $parsed instanceof \DateTimeImmutable ? $parsed : $fallback;
    }
}
