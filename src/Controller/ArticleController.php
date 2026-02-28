<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/article')]
#[IsGranted('ARTICLE_VIEW')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        // Get search and filter parameters
        $search = $request->query->get('search', '');
        $activeFilter = $request->query->get('active', 'all');

        // Build query based on filters
        $queryBuilder = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.designation', 'ASC');

        if ($search) {
            $queryBuilder
                ->andWhere('a.designation LIKE :search OR a.code LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($activeFilter === 'active') {
            $queryBuilder->andWhere('a.active = :active')->setParameter('active', true);
        } elseif ($activeFilter === 'inactive') {
            $queryBuilder->andWhere('a.active = :active')->setParameter('active', false);
        }

        $articles = $queryBuilder->getQuery()->getResult();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'search' => $search,
            'activeFilter' => $activeFilter,
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    #[IsGranted('ARTICLE_MANAGE')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été créé avec succès.');
            return $this->redirectToRoute('app_article_index');
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ARTICLE_MANAGE')]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été modifié avec succès.');
            return $this->redirectToRoute('app_article_index');
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_article_toggle', methods: ['POST'])]
    #[IsGranted('ARTICLE_MANAGE')]
    public function toggle(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $article->getId(), (string) $request->request->get('_token'))) {
            $article->setActive(!$article->isActive());
            $entityManager->flush();

            $status = $article->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "L'article a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_article_index');
    }

    #[Route('/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    #[IsGranted('ARTICLE_MANAGE')]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), (string) $request->request->get('_token'))) {
            // Check if article has associated order items or invoice items using count queries
            $orderItemCount = $entityManager->createQuery(
                'SELECT COUNT(bci.id) FROM App\Entity\BonCommandeItem bci WHERE bci.article = :article'
            )->setParameter('article', $article)
            ->getSingleScalarResult();

            $invoiceItemCount = $entityManager->createQuery(
                'SELECT COUNT(fi.id) FROM App\Entity\FactureItem fi WHERE fi.article = :article'
            )->setParameter('article', $article)
            ->getSingleScalarResult();

            if ($orderItemCount > 0 || $invoiceItemCount > 0) {
                $this->addFlash('error', 'Impossible de supprimer cet article car il est utilisé dans des documents.');
                return $this->redirectToRoute('app_article_index');
            }

            $entityManager->remove($article);
            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_article_index');
    }
}
