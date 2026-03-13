<?php

namespace App\Controller;

use App\Entity\BonLivraison;
use App\Entity\DocumentCounter;
use App\Entity\Facture;
use App\Entity\FactureItem;
use App\Entity\PdfTheme;
use App\Repository\BonLivraisonRepository;
use App\Repository\DocumentCounterRepository;
use App\Repository\FactureRepository;
use App\Repository\PdfThemeRepository;
use App\Service\FrenchAmountInWordsService;
use App\Service\PdfGenerator;
use App\Service\PdfThemeLayoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/facture', name: 'app_facture_')]
#[IsGranted('INVOICE_VIEW')]
class FactureController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, FactureRepository $factureRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $statusFilter = (string) $request->query->get('status', 'all');
        $dateFrom = (string) $request->query->get('date_from', '');
        $dateTo = (string) $request->query->get('date_to', '');

        $queryBuilder = $factureRepository->createQueryBuilder('f')
            ->leftJoin('f.bonLivraisons', 'bl')
            ->leftJoin('bl.bon_commande', 'bc')
            ->leftJoin('bc.client', 'c')
            ->addSelect('bl', 'bc', 'c')
            ->orderBy('f.created_at', 'DESC');

        if ($search !== '') {
            $queryBuilder
                ->andWhere("f.reference LIKE :search OR c.name LIKE :search OR c.client_code LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(f.client_snapshot, '$.name')) LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(f.client_snapshot, '$.client_code')) LIKE :search")
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statusFilter !== 'all') {
            $queryBuilder
                ->andWhere('f.status = :status')
                ->setParameter('status', $statusFilter);
        }

        if ($dateFrom !== '') {
            $queryBuilder
                ->andWhere('f.created_at >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom . ' 00:00:00'));
        }

        if ($dateTo !== '') {
            $queryBuilder
                ->andWhere('f.created_at <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        $invoices = $queryBuilder->getQuery()->getResult();

        return $this->render('facture/index.html.twig', [
            'invoices' => $invoices,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statusChoices' => [
                'DRAFT' => 'Brouillon',
                'ISSUED' => 'Emise',
                'UNPAID' => 'Impayee',
                'PARTIALLY_PAID' => 'Partiellement payee',
                'PAID' => 'Payee',
                'CANCELLED' => 'Annulee',
            ],
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('INVOICE_CREATE')]
    public function new(
        Request $request,
        BonLivraisonRepository $bonLivraisonRepository,
        DocumentCounterRepository $documentCounterRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $availableDeliveries = $bonLivraisonRepository->createQueryBuilder('bl')
            ->leftJoin('bl.bon_commande', 'bc')
            ->leftJoin('bc.client', 'c')
            ->leftJoin('bl.facture', 'f')
            ->addSelect('bc', 'c')
            ->andWhere('(bl.facture IS NULL OR f.status = :invoiceCancelled)')
            ->andWhere('bl.status = :validated')
            ->andWhere('bl.status != :cancelled')
            ->setParameter('validated', 'VALIDATED')
            ->setParameter('cancelled', 'CANCELLED')
            ->setParameter('invoiceCancelled', 'CANCELLED')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('bl.created_at', 'ASC')
            ->getQuery()
            ->getResult();

        if ($request->isMethod('POST')) {
            $selectedIds = array_map('intval', $request->request->all('delivery_notes'));
            $tvaRate = (float) $request->request->get('tva_rate', 19.0);
            $tvaRate = max(0, min(100, $tvaRate));
            $remise = round(max(0, (float) $request->request->get('remise', 0)), 3);

            if (count($selectedIds) === 0) {
                $this->addFlash('error', 'Selectionnez au moins un bon de livraison.');
                return $this->render('facture/new.html.twig', [
                    'available_deliveries' => $availableDeliveries,
                    'default_tva_rate' => $tvaRate,
                    'default_remise' => $remise,
                ]);
            }

            $selectedDeliveries = $bonLivraisonRepository->createQueryBuilder('bl')
                ->leftJoin('bl.bon_commande', 'bc')
                ->leftJoin('bc.client', 'c')
                ->leftJoin('bl.bonLivraisonItems', 'bli')
                ->leftJoin('bli.bon_commande_item', 'bci')
                ->leftJoin('bci.article', 'a')
                ->addSelect('bc', 'c', 'bli', 'bci', 'a')
                ->andWhere('bl.id IN (:ids)')
                ->setParameter('ids', $selectedIds)
                ->orderBy('bl.created_at', 'ASC')
                ->getQuery()
                ->getResult();

            if (count($selectedDeliveries) !== count($selectedIds)) {
                $this->addFlash('error', 'Un ou plusieurs bons de livraison selectionnes sont invalides.');
                return $this->render('facture/new.html.twig', [
                    'available_deliveries' => $availableDeliveries,
                    'default_tva_rate' => $tvaRate,
                    'default_remise' => $remise,
                ]);
            }

            $clientId = null;
            foreach ($selectedDeliveries as $delivery) {
                $currentInvoice = $delivery->getFacture();
                if ($currentInvoice !== null) {
                    if ($currentInvoice->getStatus() === 'CANCELLED') {
                        // Self-heal stale links from previously cancelled invoices.
                        $delivery->setFacture(null);
                    } else {
                        $this->addFlash('error', sprintf('Le bon %s est deja facture.', $delivery->getReference()));
                        return $this->render('facture/new.html.twig', [
                            'available_deliveries' => $availableDeliveries,
                            'default_tva_rate' => $tvaRate,
                            'default_remise' => $remise,
                        ]);
                    }
                }

                if ($delivery->getStatus() === 'CANCELLED') {
                    $this->addFlash('error', sprintf('Le bon %s est annule et ne peut pas etre facture.', $delivery->getReference()));
                    return $this->render('facture/new.html.twig', [
                        'available_deliveries' => $availableDeliveries,
                        'default_tva_rate' => $tvaRate,
                        'default_remise' => $remise,
                    ]);
                }

                if ($delivery->getStatus() !== 'VALIDATED') {
                    $this->addFlash('error', sprintf('Le bon %s doit etre confirme avant facturation.', $delivery->getReference()));
                    return $this->render('facture/new.html.twig', [
                        'available_deliveries' => $availableDeliveries,
                        'default_tva_rate' => $tvaRate,
                        'default_remise' => $remise,
                    ]);
                }

                $deliveryClient = $delivery->getBonCommande()?->getClient();
                if ($deliveryClient === null) {
                    $this->addFlash('error', sprintf('Le bon %s ne contient pas de client valide.', $delivery->getReference()));
                    return $this->render('facture/new.html.twig', [
                        'available_deliveries' => $availableDeliveries,
                        'default_tva_rate' => $tvaRate,
                        'default_remise' => $remise,
                    ]);
                }

                if ($clientId === null) {
                    $clientId = $deliveryClient->getId();
                }

                if ($clientId !== $deliveryClient->getId()) {
                    $this->addFlash('error', 'Tous les bons de livraison selectionnes doivent appartenir au meme client.');
                    return $this->render('facture/new.html.twig', [
                        'available_deliveries' => $availableDeliveries,
                        'default_tva_rate' => $tvaRate,
                        'default_remise' => $remise,
                    ]);
                }
            }

            $facture = new Facture();
            $facture->setReference($this->generateFactureReference($documentCounterRepository, $entityManager));
            $facture->setCreatedBy($this->getUser());
            $facture->setStatus('UNPAID');
            $facture->setTvaRate(number_format($tvaRate, 2, '.', ''));
            $facture->setRemise(number_format($remise, 3, '.', ''));
            $facture->setTimbre('1.000');
            $facture->setClientSnapshot($this->buildClientSnapshotFromDeliveries($selectedDeliveries));
            $facture->setDeliverySnapshot($this->buildDeliverySnapshot($selectedDeliveries));

            $totalHt = 0.0;
            $lineCount = 0;

            foreach ($selectedDeliveries as $delivery) {
                $facture->addBonLivraison($delivery);

                foreach ($delivery->getBonLivraisonItems() as $deliveryItem) {
                    $orderItem = $deliveryItem->getBonCommandeItem();
                    $article = $orderItem?->getArticle();
                    if ($orderItem === null || $article === null) {
                        continue;
                    }

                    $quantity = (float) ($deliveryItem->getQuantityDelivered() ?? 0);
                    $unitPrice = (float) ($orderItem->getUnitPriceSnapshot() ?? 0);
                    $lineTotal = $quantity * $unitPrice;

                    $factureItem = new FactureItem();
                    $factureItem->setArticle($article);
                    $factureItem->setQuantity(number_format($quantity, 2, '.', ''));
                    $factureItem->setUnitPrice(number_format($unitPrice, 3, '.', ''));
                    $factureItem->setTotalLineHt(number_format($lineTotal, 3, '.', ''));

                    $facture->addFactureItem($factureItem);

                    $totalHt += $lineTotal;
                    $lineCount++;
                }
            }

            if ($lineCount === 0) {
                $this->addFlash('error', 'Impossible de creer une facture sans lignes de livraison.');
                return $this->render('facture/new.html.twig', [
                    'available_deliveries' => $availableDeliveries,
                    'default_tva_rate' => $tvaRate,
                    'default_remise' => $remise,
                ]);
            }

            if ($remise > $totalHt) {
                $this->addFlash('error', 'La remise ne peut pas depasser le total HT de la facture.');
                return $this->render('facture/new.html.twig', [
                    'available_deliveries' => $availableDeliveries,
                    'default_tva_rate' => $tvaRate,
                    'default_remise' => $remise,
                ]);
            }

            $netTotalHt = $totalHt - $remise;
            $tvaAmount = $netTotalHt * ($tvaRate / 100);
            $timbre = 1.000;
            $totalTtc = $netTotalHt + $tvaAmount + $timbre;

            $facture->setTotalHt(number_format($netTotalHt, 3, '.', ''));
            $facture->setTvaAmount(number_format($tvaAmount, 3, '.', ''));
            $facture->setTotalTtc(number_format($totalTtc, 3, '.', ''));

            $entityManager->persist($facture);
            $entityManager->flush();

            $this->addFlash('success', sprintf('La facture %s a ete creee avec succes.', $facture->getReference()));

            return $this->redirectToRoute('app_facture_show', ['id' => $facture->getId()]);
        }

        return $this->render('facture/new.html.twig', [
            'available_deliveries' => $availableDeliveries,
            'default_tva_rate' => 19.0,
            'default_remise' => 0,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}/pdf', name: 'pdf', methods: ['GET'])]
    #[IsGranted('INVOICE_VIEW')]
    public function pdf(
        Facture $facture,
        PdfThemeRepository $pdfThemeRepository,
        PdfThemeLayoutService $layoutService,
        PdfGenerator $pdfGenerator,
        FrenchAmountInWordsService $amountInWordsService
    ): Response {
        $theme = $pdfThemeRepository->findActiveByType(PdfTheme::TYPE_INVOICE);
        $imagePath = $theme?->getImagePath() ?? '/uploads/pdf-themes/theme-white.jpg';
        $anchors = $layoutService->normalize(PdfTheme::TYPE_INVOICE, $theme?->getAnchors() ?? []);

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $absoluteImagePath = $projectDir . '/public' . $imagePath;
        $backgroundDataUri = $this->toImageDataUri($absoluteImagePath);

        $amountInWords = $amountInWordsService->toDinarsAndMillimes((float) $facture->getTotalTtc());

        return $pdfGenerator->renderInline('pdf/invoice.html.twig', [
            'invoice' => $facture,
            'anchors' => $anchors,
            'backgroundDataUri' => $backgroundDataUri,
            'amountInWords' => $amountInWords,
        ], $facture->getReference() . '.pdf');
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    #[IsGranted('INVOICE_CANCEL')]
    public function cancel(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('cancel' . $facture->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_facture_show', ['id' => $facture->getId()]);
        }

        if ($facture->getStatus() === 'CANCELLED') {
            $this->captureInvoiceSnapshotsIfMissing($facture);

            $releasedCount = 0;
            foreach ($facture->getBonLivraisons() as $bl) {
                if ($bl->getFacture() !== null) {
                    $bl->setFacture(null);
                    $releasedCount++;
                }
            }

            if ($releasedCount > 0) {
                $entityManager->flush();
                $this->addFlash('success', sprintf('Facture deja annulee. %d bon(s) de livraison libere(s).', $releasedCount));
            } else {
                $this->addFlash('warning', 'Cette facture est deja annulee.');
            }
            return $this->redirectToRoute('app_facture_show', ['id' => $facture->getId()]);
        }

        if ((float) $facture->getTotalPaid() > 0) {
            $this->addFlash('error', 'Impossible d\'annuler une facture avec paiements associes.');
            return $this->redirectToRoute('app_facture_show', ['id' => $facture->getId()]);
        }

        $this->captureInvoiceSnapshotsIfMissing($facture);
        $facture->setStatus('CANCELLED');
        $facture->setCancelledAt(new \DateTime());

        // Release linked BLs so they can be invoiced again
        foreach ($facture->getBonLivraisons() as $bl) {
            $bl->setFacture(null);
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf('La facture %s a ete annulee.', $facture->getReference()));

        return $this->redirectToRoute('app_facture_show', ['id' => $facture->getId()]);
    }

    private function generateFactureReference(
        DocumentCounterRepository $documentCounterRepository,
        EntityManagerInterface $entityManager
    ): string {
        $year = (int) (new \DateTimeImmutable())->format('Y');

        /** @var DocumentCounter $counter */
        $counter = $documentCounterRepository->getOrCreateCounter('INVOICE', $year);
        $nextNumber = $counter->getNextNumber();

        $entityManager->persist($counter);

        return sprintf('FAC-%d-%04d', $year, $nextNumber);
    }

    /**
     * @param list<BonLivraison> $deliveries
     */
    private function buildClientSnapshotFromDeliveries(array $deliveries): ?array
    {
        $first = $deliveries[0] ?? null;
        if (!$first instanceof BonLivraison) {
            return null;
        }

        $order = $first->getBonCommande();
        if ($order === null) {
            return null;
        }

        $orderSnapshot = $order->getClientSnapshot();
        if ($orderSnapshot !== []) {
            return $orderSnapshot;
        }

        $client = $order->getClient();
        if ($client === null) {
            return null;
        }

        return [
            'id' => $client->getId(),
            'client_code' => $client->getClientCode(),
            'name' => $client->getName(),
            'matricule_fiscale' => $client->getMatriculeFiscale(),
            'address' => $client->getAddress(),
        ];
    }

    /**
     * @param list<BonLivraison> $deliveries
     * @return list<array<string, mixed>>
     */
    private function buildDeliverySnapshot(array $deliveries): array
    {
        $snapshot = [];

        foreach ($deliveries as $delivery) {
            if (!$delivery instanceof BonLivraison) {
                continue;
            }

            $order = $delivery->getBonCommande();
            $snapshot[] = [
                'id' => $delivery->getId(),
                'reference' => $delivery->getReference(),
                'status' => $delivery->getStatus(),
                'created_at' => $delivery->getCreatedAt()?->format('Y-m-d H:i:s'),
                'order_id' => $order?->getId(),
                'order_reference' => $order?->getReference(),
            ];
        }

        return $snapshot;
    }

    private function captureInvoiceSnapshotsIfMissing(Facture $facture): void
    {
        $deliveries = $facture->getBonLivraisons()->toArray();

        if ($facture->getClientSnapshot() === null) {
            $facture->setClientSnapshot($this->buildClientSnapshotFromDeliveries($deliveries));
        }

        if ($facture->getDeliverySnapshot() === null || $facture->getDeliverySnapshot() === []) {
            $facture->setDeliverySnapshot($this->buildDeliverySnapshot($deliveries));
        }
    }

    private function toImageDataUri(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            return '';
        }

        $content = file_get_contents($absolutePath);
        if ($content === false) {
            return '';
        }

        $mimeType = str_ends_with(strtolower($absolutePath), '.png') ? 'image/png' : 'image/jpeg';

        return sprintf('data:%s;base64,%s', $mimeType, base64_encode($content));
    }
}
