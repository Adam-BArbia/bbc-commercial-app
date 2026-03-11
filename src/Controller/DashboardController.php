<?php

namespace App\Controller;

use App\Repository\BonCommandeRepository;
use App\Repository\BonLivraisonRepository;
use App\Repository\ClientRepository;
use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(
        BonCommandeRepository $bonCommandeRepository,
        BonLivraisonRepository $bonLivraisonRepository,
        FactureRepository $factureRepository,
        ClientRepository $clientRepository,
    ): Response {
        // Orders in progress: confirmed or partially delivered
        $ordersInProgress = $bonCommandeRepository->createQueryBuilder('bc')
            ->select('COUNT(bc.id)')
            ->where('bc.status IN (:statuses)')
            ->setParameter('statuses', ['CONFIRMED', 'PARTIALLY_DELIVERED'])
            ->getQuery()
            ->getSingleScalarResult();

        // Delivery notes validated but not yet invoiced
        $deliveriesNotInvoiced = $bonLivraisonRepository->createQueryBuilder('bl')
            ->select('COUNT(bl.id)')
            ->where('bl.status = :status')
            ->andWhere('bl.facture IS NULL')
            ->setParameter('status', 'VALIDATED')
            ->getQuery()
            ->getSingleScalarResult();

        // Invoices unpaid or partially paid
        $invoicesUnpaid = $factureRepository->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.status IN (:statuses)')
            ->setParameter('statuses', ['UNPAID', 'PARTIALLY_PAID'])
            ->getQuery()
            ->getSingleScalarResult();

        // Active clients
        $activeClients = $clientRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Recent orders (last 5)
        $recentOrders = $bonCommandeRepository->createQueryBuilder('bc')
            ->leftJoin('bc.client', 'c')
            ->addSelect('c')
            ->orderBy('bc.created_at', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Invoices needing attention (unpaid/partially paid, most recent 5)
        $pendingInvoices = $factureRepository->createQueryBuilder('f')
            ->where('f.status IN (:statuses)')
            ->setParameter('statuses', ['UNPAID', 'PARTIALLY_PAID'])
            ->orderBy('f.created_at', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/index.html.twig', [
            'user'                   => $this->getUser(),
            'ordersInProgress'       => (int) $ordersInProgress,
            'deliveriesNotInvoiced'  => (int) $deliveriesNotInvoiced,
            'invoicesUnpaid'         => (int) $invoicesUnpaid,
            'activeClients'          => (int) $activeClients,
            'recentOrders'           => $recentOrders,
            'pendingInvoices'        => $pendingInvoices,
        ]);
    }
}
