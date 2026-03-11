<?php

namespace App\Controller;

use App\Entity\BonCommande;
use App\Entity\BonLivraison;
use App\Entity\BonLivraisonItem;
use App\Entity\DocumentCounter;
use App\Form\BonLivraisonType;
use App\Repository\BonCommandeRepository;
use App\Repository\BonLivraisonRepository;
use App\Repository\DocumentCounterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bon-livraison', name: 'app_bon_livraison_')]
#[IsGranted('ROLE_USER')]
class BonLivraisonController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        BonLivraisonRepository $repository,
        BonCommandeRepository $bonCommandeRepository
    ): Response {
        $searchQuery  = (string) $request->query->get('search', '');
        $statusFilter = (string) $request->query->get('status', '');
        $dateFrom     = (string) $request->query->get('date_from', '');
        $dateTo       = (string) $request->query->get('date_to', '');

        $query = $repository
            ->createQueryBuilder('bl')
            ->leftJoin('bl.bon_commande', 'bc')
            ->leftJoin('bc.client', 'c')
            ->orderBy('bl.created_at', 'DESC');

        if ($searchQuery !== '') {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('bl.reference', ':search'),
                $query->expr()->like('c.name', ':search'),
                $query->expr()->like('bc.reference', ':search')
            ))
            ->setParameter('search', '%' . $searchQuery . '%');
        }

        if ($statusFilter !== '') {
            $query->andWhere('bl.status = :status')
                ->setParameter('status', $statusFilter);
        }

        if ($dateFrom !== '') {
            $query->andWhere('bl.created_at >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom . ' 00:00:00'));
        }

        if ($dateTo !== '') {
            $query->andWhere('bl.created_at <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        $deliveryNotes = $query->getQuery()->getResult();

        $availableOrders = $bonCommandeRepository
            ->createQueryBuilder('bc')
            ->leftJoin('bc.client', 'c')
            ->andWhere('bc.status != :cancelled')
            ->setParameter('cancelled', 'CANCELLED')
            ->orderBy('bc.reference', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('bon_livraison/index.html.twig', [
            'delivery_notes'  => $deliveryNotes,
            'search_query'    => $searchQuery,
            'status_filter'   => $statusFilter,
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
            'available_orders' => $availableOrders,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        DocumentCounterRepository $documentCounterRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $bonLivraison = new BonLivraison();
        $form = $this->createForm(BonLivraisonType::class, $bonLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bonCommande = $bonLivraison->getBonCommande();

            if (!$bonCommande) {
                $this->addFlash('error', 'Veuillez sélectionner une commande.');
                return $this->redirectToRoute('app_bon_livraison_new');
            }

            // Check if there are any order items to deliver
            if ($bonCommande->getBonCommandeItems()->count() === 0) {
                $this->addFlash('error', 'La commande sélectionnée ne contient aucun article.');
                return $this->redirectToRoute('app_bon_livraison_new');
            }

            // Process delivery items from request
            $deliveryItems = $request->request->all('delivery_items') ?? [];

            if (empty($deliveryItems)) {
                $this->addFlash('error', 'Veuillez ajouter au moins une ligne de livraison.');
                return $this->redirectToRoute('app_bon_livraison_new');
            }

            // Generate reference
            $reference = $this->generateDeliveryNoteReference($documentCounterRepository, $entityManager);
            $bonLivraison->setReference($reference);
            $bonLivraison->setCreatedBy($this->getUser());
            $bonLivraison->setStatus('DRAFT');

            // Add delivery items
            $hasValidItems = false;
            $validationErrors = [];
            
            foreach ($deliveryItems as $orderItemId => $quantity) {
                $quantity = (int) $quantity;
                if ($quantity > 0) {
                    $orderItem = $bonCommande->getBonCommandeItems()->filter(
                        fn($item) => $item->getId() == $orderItemId
                    )->first();

                    if ($orderItem) {
                        $remainingQty = (int)($orderItem->getRemainingQuantity() ?? $orderItem->getQuantity());
                        
                        if ($quantity > $remainingQty) {
                            $validationErrors[] = sprintf(
                                'La quantité %d pour "%s" dépasse la quantité restante de %d unités.',
                                $quantity,
                                $orderItem->getArticle()?->getDesignation() ?? 'Article',
                                $remainingQty
                            );
                            continue;
                        }

                        $hasValidItems = true;
                        $deliveryItem = new BonLivraisonItem();
                        $deliveryItem->setBonLivraison($bonLivraison);
                        $deliveryItem->setBonCommandeItem($orderItem);
                        $deliveryItem->setQuantityDelivered((string)$quantity);
                        
                        $bonLivraison->addBonLivraisonItem($deliveryItem);
                    }
                }
            }
            
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('bon_livraison/new.html.twig', [
                    'form' => $form,
                    'bon_livraison' => $bonLivraison,
                ]);
            }

            if (!$hasValidItems) {
                $this->addFlash('error', 'Veuillez entrer au moins une quantité positive.');
                return $this->redirectToRoute('app_bon_livraison_new');
            }

            $entityManager->persist($bonLivraison);

            // Update order status if this is the first delivery
            if (!$bonCommande->hasDeliveryNotes()) {
                $bonCommande->setStatus('PARTIALLY_DELIVERED');
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf('Le bon de livraison %s a été créé avec succès.', $reference));
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        return $this->render('bon_livraison/new.html.twig', [
            'form' => $form,
            'bon_livraison' => $bonLivraison,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(BonLivraison $bonLivraison): Response
    {
        return $this->render('bon_livraison/show.html.twig', [
            'bon_livraison' => $bonLivraison,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function edit(
        Request $request,
        BonLivraison $bonLivraison,
        EntityManagerInterface $entityManager
    ): Response {
        // Only allow editing if not invoiced
        if (!$bonLivraison->canBeEdited()) {
            $this->addFlash('error', 'Ce bon de livraison ne peut pas être modifié.');
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        if ($request->isMethod('POST')) {
            $deliveryItems = $request->request->all('delivery_items') ?? [];
            $bonCommande = $bonLivraison->getBonCommande();

            if (empty($deliveryItems)) {
                $this->addFlash('error', 'Veuillez entrer au moins une quantité.');
                return $this->redirectToRoute('app_bon_livraison_edit', ['id' => $bonLivraison->getId()]);
            }

            // Validate quantities before clearing (exclude current BL from delivered count)
            $validationErrors = [];
            foreach ($deliveryItems as $orderItemId => $quantity) {
                $quantity = (float) $quantity;
                if ($quantity > 0) {
                    $orderItem = $bonCommande->getBonCommandeItems()->filter(
                        fn($item) => $item->getId() == $orderItemId
                    )->first();

                    if ($orderItem) {
                        // Calculate delivered excluding current BL being edited
                        $totalDelivered = (float) $orderItem->getDeliveredQuantity();
                        $currentBLitem = $bonLivraison->getBonLivraisonItems()->filter(
                            fn($item) => $item->getBonCommandeItem()->getId() == $orderItemId
                        )->first();
                        $currentQty = $currentBLitem ? (float) $currentBLitem->getQuantityDelivered() : 0;
                        $deliveredExcludingThis = $totalDelivered - $currentQty;
                        
                        // Check if new quantity + other deliveries exceeds ordered
                        $orderedQty = (float) $orderItem->getQuantity();
                        if (($quantity + $deliveredExcludingThis) > $orderedQty) {
                            $validationErrors[] = sprintf(
                                'La quantité %.2f pour "%s" dépasse la quantité commandée. Maximum autorisé: %.2f (déjà livré par autres BL: %.2f)',
                                $quantity,
                                $orderItem->getArticle()?->getDesignation() ?? 'Article',
                                $orderedQty - $deliveredExcludingThis,
                                $deliveredExcludingThis
                            );
                        }
                    }
                }
            }

            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_bon_livraison_edit', ['id' => $bonLivraison->getId()]);
            }

            // Clear existing items and rebuild
            $bonLivraison->getBonLivraisonItems()->clear();

            $hasValidItems = false;
            foreach ($deliveryItems as $orderItemId => $quantity) {
                $quantity = (float) $quantity;
                if ($quantity > 0) {
                    $hasValidItems = true;
                    $orderItem = $bonCommande->getBonCommandeItems()->filter(
                        fn($item) => $item->getId() == $orderItemId
                    )->first();

                    if ($orderItem) {
                        $deliveryItem = new BonLivraisonItem();
                        $deliveryItem->setBonLivraison($bonLivraison);
                        $deliveryItem->setBonCommandeItem($orderItem);
                        $deliveryItem->setQuantityDelivered((string)$quantity);
                        $bonLivraison->addBonLivraisonItem($deliveryItem);
                    }
                }
            }

            if (!$hasValidItems) {
                $this->addFlash('error', 'Veuillez entrer au moins une quantité positive.');
                return $this->redirectToRoute('app_bon_livraison_edit', ['id' => $bonLivraison->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Le bon de livraison a été modifié avec succès.');
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        return $this->render('bon_livraison/edit.html.twig', [
            'bon_livraison' => $bonLivraison,
            'bon_commande' => $bonLivraison->getBonCommande(),
        ]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function cancel(
        Request $request,
        BonLivraison $bonLivraison,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('cancel' . $bonLivraison->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        if ($bonLivraison->isInvoiced()) {
            $this->addFlash('error', 'Impossible d\'annuler un bon de livraison facturé.');
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        $bonLivraison->setStatus('CANCELLED');
        $bonLivraison->setCancelledAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', sprintf('Le bon de livraison %s a été annulé.', $bonLivraison->getReference()));
        return $this->redirectToRoute('app_bon_livraison_index');
    }

    #[Route('/{id}/confirm', name: 'confirm', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function confirm(
        Request $request,
        BonLivraison $bonLivraison,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('confirm' . $bonLivraison->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        if ($bonLivraison->getStatus() === 'CANCELLED') {
            $this->addFlash('error', 'Impossible de confirmer un bon de livraison annulé.');
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        if ($bonLivraison->getStatus() === 'VALIDATED') {
            $this->addFlash('warning', 'Ce bon de livraison est déjà confirmé.');
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        $bonLivraison->setStatus('VALIDATED');
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le bon de livraison %s a été confirmé.', $bonLivraison->getReference()));
        return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        BonLivraison $bonLivraison,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete' . $bonLivraison->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        if ($bonLivraison->isInvoiced()) {
            $this->addFlash('error', 'Impossible de supprimer un bon de livraison facturé.');
            return $this->redirectToRoute('app_bon_livraison_show', ['id' => $bonLivraison->getId()]);
        }

        $reference = $bonLivraison->getReference();
        $entityManager->remove($bonLivraison);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le bon de livraison %s a été supprimé.', $reference));
        return $this->redirectToRoute('app_bon_livraison_index');
    }

    /**
     * Helper method to generate delivery note reference
     */
    private function generateDeliveryNoteReference(
        DocumentCounterRepository $documentCounterRepository,
        EntityManagerInterface $entityManager
    ): string {
        $year = (int) date('Y');
        $counter = $documentCounterRepository->findOneBy(['document_type' => 'BL', 'year' => $year]);

        if (!$counter) {
            $counter = new DocumentCounter();
            $counter->setDocumentType('BL');
            $counter->setYear($year);
            $counter->setLastNumber(0);
            $entityManager->persist($counter);
        }

        $nextNumber = $counter->getLastNumber() + 1;
        $counter->setLastNumber($nextNumber);
        $entityManager->flush();

        return sprintf('BL-%d-%04d', $year, $nextNumber);
    }

    #[Route('/api/bon-commande/{id}/items', name: 'api_bon_commande_items', methods: ['GET'])]
    public function getOrderItems(BonCommande $bonCommande): JsonResponse
    {
        $items = [];
        foreach ($bonCommande->getBonCommandeItems() as $orderItem) {
            $items[] = [
                'id' => $orderItem->getId(),
                'article_name' => $orderItem->getArticle()?->getDesignation() ?? 'Unknown',
                'quantity' => $orderItem->getQuantity(),
                'delivered_quantity' => $orderItem->getDeliveredQuantity(),
                'remaining_quantity' => $orderItem->getRemainingQuantity(),
            ];
        }

        return new JsonResponse(['items' => $items]);
    }
}
