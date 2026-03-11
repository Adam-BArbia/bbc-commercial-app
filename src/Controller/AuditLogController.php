<?php

namespace App\Controller;

use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/audit-log', name: 'app_audit_log_')]
#[IsGranted('AUDIT_VIEW')]
class AuditLogController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, AuditLogRepository $auditLogRepository, UserRepository $userRepository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 50;
        $table = trim((string) $request->query->get('table', ''));
        $action = trim((string) $request->query->get('action', ''));
        $userId = (int) $request->query->get('user', 0);
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));

        $qb = $auditLogRepository->createQueryBuilder('al')
            ->leftJoin('al.performed_by', 'u')
            ->addSelect('u')
            ->orderBy('al.performed_at', 'DESC')
            ->addOrderBy('al.id', 'DESC');

        if ($table !== '') {
            $qb->andWhere('al.table_name LIKE :table')
                ->setParameter('table', '%' . $table . '%');
        }

        if ($action !== '') {
            $qb->andWhere('al.action = :action')
                ->setParameter('action', $action);
        }

        if ($userId > 0) {
            $qb->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        if ($dateFrom !== '') {
            $qb->andWhere('al.performed_at >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom . ' 00:00:00'));
        }

        if ($dateTo !== '') {
            $qb->andWhere('al.performed_at <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        $countQb = clone $qb;
        $totalItems = (int) $countQb
            ->select('COUNT(al.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $logs = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return $this->render('audit_log/index.html.twig', [
            'logs' => $logs,
            'users' => $userRepository->createQueryBuilder('u')->orderBy('u.name', 'ASC')->getQuery()->getResult(),
            'filters' => [
                'table' => $table,
                'action' => $action,
                'user' => $userId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
            ],
        ]);
    }
}
