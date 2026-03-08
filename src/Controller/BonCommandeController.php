<?php

namespace App\Controller;

use App\Entity\BonCommande;
use App\Entity\Client;
use App\Entity\DocumentCounter;
use App\Form\BonCommandeType;
use App\Repository\BonCommandeRepository;
use App\Repository\DocumentCounterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bon-commande')]
#[IsGranted('ORDER_VIEW')]
class BonCommandeController extends AbstractController
{
    #[Route('/', name: 'app_bon_commande_index', methods: ['GET'])]
    public function index(Request $request, BonCommandeRepository $bonCommandeRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $statusFilter = (string) $request->query->get('status', 'all');

        $queryBuilder = $bonCommandeRepository->createQueryBuilder('bc')
            ->leftJoin('bc.client', 'c')
            ->addSelect('c')
            ->orderBy('bc.created_at', 'DESC');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('bc.reference LIKE :search OR c.name LIKE :search OR c.client_code LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statusFilter !== 'all') {
            $queryBuilder
                ->andWhere('bc.status = :status')
                ->setParameter('status', $statusFilter);
        }

        $orders = $queryBuilder->getQuery()->getResult();

        return $this->render('bon_commande/index.html.twig', [
            'orders' => $orders,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'statusChoices' => [
                'DRAFT' => 'Brouillon',
                'CONFIRMED' => 'Confirmee',
                'PARTIALLY_DELIVERED' => 'Partiellement livree',
                'DELIVERED' => 'Livree',
                'CANCELLED' => 'Annulee',
            ],
        ]);
    }

    #[Route('/new', name: 'app_bon_commande_new', methods: ['GET', 'POST'])]
    #[IsGranted('ORDER_CREATE')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentCounterRepository $documentCounterRepository
    ): Response {
        $order = new BonCommande();
        $form = $this->createForm(BonCommandeType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->getBonCommandeItems()->count() === 0) {
                $form->addError(new FormError('Ajoutez au moins une ligne a la commande.'));
            } else {
                $order->setReference($this->generateOrderReference($documentCounterRepository, $entityManager));
                $order->setStatus('CONFIRMED');
                $order->setCreatedBy($this->getUser());
                $order->setClientSnapshot($this->buildClientSnapshot($order->getClient()));

                $entityManager->persist($order);
                $entityManager->flush();

                $this->addFlash('success', 'Le bon de commande a ete cree avec succes.');

                return $this->redirectToRoute('app_bon_commande_show', ['id' => $order->getId()]);
            }
        }

        return $this->render('bon_commande/new.html.twig', [
            'form' => $form,
            'order' => $order,
        ]);
    }

    #[Route('/{id}', name: 'app_bon_commande_show', methods: ['GET'])]
    public function show(BonCommande $bonCommande): Response
    {
        return $this->render('bon_commande/show.html.twig', [
            'order' => $bonCommande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bon_commande_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ORDER_EDIT')]
    public function edit(Request $request, BonCommande $bonCommande, EntityManagerInterface $entityManager): Response
    {
        if (!$bonCommande->canBeEdited()) {
            $this->addFlash('error', 'Ce bon de commande ne peut plus etre modifie (livraison existante ou document annule).');
            return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
        }

        $form = $this->createForm(BonCommandeType::class, $bonCommande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($bonCommande->getBonCommandeItems()->count() === 0) {
                $form->addError(new FormError('Ajoutez au moins une ligne a la commande.'));
            } else {
                $bonCommande->setClientSnapshot($this->buildClientSnapshot($bonCommande->getClient()));
                $entityManager->flush();

                $this->addFlash('success', 'Le bon de commande a ete modifie avec succes.');

                return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
            }
        }

        return $this->render('bon_commande/edit.html.twig', [
            'form' => $form,
            'order' => $bonCommande,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_bon_commande_cancel', methods: ['POST'])]
    #[IsGranted('ORDER_CANCEL')]
    public function cancel(Request $request, BonCommande $bonCommande, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('cancel' . $bonCommande->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
        }

        if ($bonCommande->getStatus() === 'CANCELLED') {
            $this->addFlash('warning', 'Ce bon de commande est deja annule.');
            return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
        }

        if ($bonCommande->hasDeliveryNotes()) {
            $this->addFlash('error', 'Impossible d\'annuler une commande avec des bons de livraison associes.');
            return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
        }

        $bonCommande->setStatus('CANCELLED');
        $bonCommande->setCancelledAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Le bon de commande a ete annule logiquement.');

        return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_bon_commande_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, BonCommande $bonCommande, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $bonCommande->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
        }

        if ($bonCommande->hasDeliveryNotes()) {
            $this->addFlash('error', 'Suppression impossible: ce bon de commande est lie a un ou plusieurs bons de livraison.');
            return $this->redirectToRoute('app_bon_commande_show', ['id' => $bonCommande->getId()]);
        }

        $reference = $bonCommande->getReference();
        $entityManager->remove($bonCommande);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le bon de commande %s a ete supprime physiquement.', $reference));

        return $this->redirectToRoute('app_bon_commande_index');
    }

    private function generateOrderReference(
        DocumentCounterRepository $documentCounterRepository,
        EntityManagerInterface $entityManager
    ): string {
        $year = (int) (new \DateTimeImmutable())->format('Y');

        /** @var DocumentCounter $counter */
        $counter = $documentCounterRepository->getOrCreateCounter('ORDER', $year);
        $nextNumber = $counter->getNextNumber();

        $entityManager->persist($counter);

        return sprintf('BC-%d-%04d', $year, $nextNumber);
    }

    /**
     * Keep immutable client snapshot in the order for downstream documents.
     *
     * @return array<string, mixed>
     */
    private function buildClientSnapshot(?Client $client): array
    {
        if ($client === null) {
            return [];
        }

        return [
            'id' => $client->getId(),
            'client_code' => $client->getClientCode(),
            'name' => $client->getName(),
            'matricule_fiscale' => $client->getMatriculeFiscale(),
            'address' => $client->getAddress(),
        ];
    }
}
