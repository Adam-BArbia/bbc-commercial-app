<?php

namespace App\Controller;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/roles')]
#[IsGranted('ROLE_ADMIN')]
class RoleController extends AbstractController
{
    #[Route('', name: 'admin_role_index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository): Response
    {
        return $this->render('admin/role/index.html.twig', [
            'roles' => $roleRepository->createQueryBuilder('r')
                ->leftJoin('r.privileges', 'p')
                ->addSelect('p')
                ->leftJoin('r.users', 'u')
                ->addSelect('u')
                ->orderBy('r.name', 'ASC')
                ->getQuery()
                ->getResult(),
        ]);
    }

    #[Route('/new', name: 'admin_role_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($role);
            $entityManager->flush();

            $this->addFlash('success', 'Rôle créé avec succès.');

            return $this->redirectToRoute('admin_role_index');
        }

        return $this->render('admin/role/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_role_edit', methods: ['GET', 'POST'])]
    public function edit(Role $role, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Rôle modifié avec succès.');

            return $this->redirectToRoute('admin_role_index');
        }

        return $this->render('admin/role/edit.html.twig', [
            'roleEntity' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_role_delete', methods: ['POST'])]
    public function delete(Role $role, Request $request, EntityManagerInterface $entityManager, RoleRepository $roleRepository): Response
    {
        if (!$this->isCsrfTokenValid('delete-role-'.$role->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $roleId = $role->getId();

        // Use fresh queries to avoid Doctrine cache issues
        $usersCount = $roleRepository->createQueryBuilder('r')
            ->select('COUNT(u.id)')
            ->leftJoin('r.users', 'u')
            ->where('r.id = :roleId')
            ->setParameter('roleId', $roleId)
            ->getQuery()
            ->getSingleScalarResult();

        if ($usersCount > 0) {
            $this->addFlash('error', 'Impossible de supprimer un rôle assigné à des utilisateurs.');
            return $this->redirectToRoute('admin_role_index');
        }

        // Delete from join table directly using native SQL
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM role_privilege WHERE role_id = ?', [$roleId]);
        
        $entityManager->remove($role);
        $entityManager->flush();

        $this->addFlash('success', 'Rôle supprimé.');

        return $this->redirectToRoute('admin_role_index');
    }
}
