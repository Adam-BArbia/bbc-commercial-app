<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('CLIENT_VIEW')]
class ClientController extends AbstractController
{
    #[Route('/', name: 'app_client_index', methods: ['GET'])]
    public function index(Request $request, ClientRepository $clientRepository): Response
    {
        // Get search and filter parameters
        $search = $request->query->get('search', '');
        $activeFilter = $request->query->get('active', 'all');

        // Build query based on filters
        $queryBuilder = $clientRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        if ($search) {
            $queryBuilder
                ->andWhere('c.name LIKE :search OR c.client_code LIKE :search OR c.matricule_fiscale LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($activeFilter === 'active') {
            $queryBuilder->andWhere('c.active = :active')->setParameter('active', true);
        } elseif ($activeFilter === 'inactive') {
            $queryBuilder->andWhere('c.active = :active')->setParameter('active', false);
        }

        $clients = $queryBuilder->getQuery()->getResult();

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
            'search' => $search,
            'activeFilter' => $activeFilter,
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    #[IsGranted('CLIENT_MANAGE')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($client);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a été créé avec succès.');
            return $this->redirectToRoute('app_client_index');
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    #[IsGranted('CLIENT_MANAGE')]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le client a été modifié avec succès.');
            return $this->redirectToRoute('app_client_index');
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_client_toggle', methods: ['POST'])]
    #[IsGranted('CLIENT_MANAGE')]
    public function toggle(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $client->getId(), (string) $request->request->get('_token'))) {
            $client->setActive(!$client->isActive());
            $entityManager->flush();

            $status = $client->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Le client a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_client_index');
    }

    #[Route('/{id}/delete', name: 'app_client_delete', methods: ['POST'])]
    #[IsGranted('CLIENT_MANAGE')]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $client->getId(), (string) $request->request->get('_token'))) {
            // Check if client has associated orders using a count query (avoids loading full entities)
            $orderCount = $entityManager->createQuery(
                'SELECT COUNT(bc.id) FROM App\Entity\BonCommande bc WHERE bc.client = :client'
            )->setParameter('client', $client)
            ->getSingleScalarResult();

            if ($orderCount > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce client car il possède des bons de commande.');
                return $this->redirectToRoute('app_client_index');
            }

            $entityManager->remove($client);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_client_index');
    }
}
