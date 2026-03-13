<?php

namespace App\Controller;

use App\Entity\PdfTheme;
use App\Form\PdfThemeType;
use App\Repository\PdfThemeRepository;
use App\Service\PdfThemeLayoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pdf-themes', name: 'admin_pdf_theme_')]
#[IsGranted('PDF_THEME_MANAGE')]
class PdfThemeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PdfThemeRepository $pdfThemeRepository): Response
    {
        return $this->render('admin/pdf_theme/index.html.twig', [
            'themes' => $pdfThemeRepository->findBy([], ['documentType' => 'ASC', 'name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PdfThemeRepository $pdfThemeRepository,
        PdfThemeLayoutService $layoutService
    ): Response {
        $theme = new PdfTheme();
        $theme->setDocumentType(PdfTheme::TYPE_DELIVERY);
        $theme->setAnchors($layoutService->getDefaultAnchors(PdfTheme::TYPE_DELIVERY));

        $form = $this->createForm(PdfThemeType::class, $theme, [
            'anchor_values' => $theme->getAnchors(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedImage = $form->get('imageFile')->getData();
            if (!$uploadedImage instanceof UploadedFile) {
                $this->addFlash('error', 'Veuillez charger une image de thème.');

                return $this->render('admin/pdf_theme/new.html.twig', [
                    'form' => $form,
                ]);
            }

            $theme->setImagePath($this->moveThemeImage($uploadedImage));
            $theme->setAnchors($this->extractAnchors($form, $layoutService, (string) $theme->getDocumentType()));
            $theme->setUpdatedAt(new \DateTimeImmutable());

            if ($theme->isActive()) {
                $pdfThemeRepository->deactivateTypeThemes((string) $theme->getDocumentType());
            }

            $entityManager->persist($theme);
            $entityManager->flush();

            $this->addFlash('success', 'Thème PDF créé avec succès.');

            return $this->redirectToRoute('admin_pdf_theme_index');
        }

        return $this->render('admin/pdf_theme/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        PdfTheme $theme,
        Request $request,
        EntityManagerInterface $entityManager,
        PdfThemeRepository $pdfThemeRepository,
        PdfThemeLayoutService $layoutService
    ): Response {
        $form = $this->createForm(PdfThemeType::class, $theme, [
            'anchor_values' => $layoutService->normalize((string) $theme->getDocumentType(), $theme->getAnchors()),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedImage = $form->get('imageFile')->getData();
            if ($uploadedImage instanceof UploadedFile) {
                $theme->setImagePath($this->moveThemeImage($uploadedImage));
            }

            $theme->setAnchors($this->extractAnchors($form, $layoutService, (string) $theme->getDocumentType()));
            $theme->setUpdatedAt(new \DateTimeImmutable());

            if ($theme->isActive()) {
                $pdfThemeRepository->deactivateTypeThemes((string) $theme->getDocumentType(), $theme->getId());
            }

            $entityManager->flush();
            $this->addFlash('success', 'Thème PDF modifié avec succès.');

            return $this->redirectToRoute('admin_pdf_theme_index');
        }

        return $this->render('admin/pdf_theme/edit.html.twig', [
            'theme' => $theme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activate', name: 'activate', methods: ['POST'])]
    public function activate(
        PdfTheme $theme,
        Request $request,
        EntityManagerInterface $entityManager,
        PdfThemeRepository $pdfThemeRepository
    ): Response {
        if (!$this->isCsrfTokenValid('activate-pdf-theme-' . $theme->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $pdfThemeRepository->deactivateTypeThemes((string) $theme->getDocumentType(), $theme->getId());
        $theme->setIsActive(true);
        $theme->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Thème activé pour ce type de document.');

        return $this->redirectToRoute('admin_pdf_theme_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(PdfTheme $theme, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-pdf-theme-' . $theme->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($theme->isActive()) {
            $this->addFlash('error', 'Impossible de supprimer un thème actif.');

            return $this->redirectToRoute('admin_pdf_theme_index');
        }

        $entityManager->remove($theme);
        $entityManager->flush();

        $this->addFlash('success', 'Thème supprimé.');

        return $this->redirectToRoute('admin_pdf_theme_index');
    }

    private function moveThemeImage(UploadedFile $uploadedFile): string
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/pdf-themes';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $extension = $uploadedFile->guessExtension() ?: 'jpg';
        $fileName = sprintf('theme-%s.%s', bin2hex(random_bytes(8)), $extension);
        $uploadedFile->move($uploadDir, $fileName);

        return '/uploads/pdf-themes/' . $fileName;
    }

    /**
     * @return array<string, float>
     */
    private function extractAnchors($form, PdfThemeLayoutService $layoutService, string $documentType): array
    {
        return $layoutService->normalize($documentType, [
            'header_x' => $form->get('header_x')->getData(),
            'header_y' => $form->get('header_y')->getData(),
            'header_w' => $form->get('header_w')->getData(),
            'header_h' => $form->get('header_h')->getData(),
            'title_x' => $form->get('title_x')->getData(),
            'title_y' => $form->get('title_y')->getData(),
            'title_w' => $form->get('title_w')->getData(),
            'title_h' => $form->get('title_h')->getData(),
            'client_x' => $form->get('client_x')->getData(),
            'client_y' => $form->get('client_y')->getData(),
            'client_w' => $form->get('client_w')->getData(),
            'client_h' => $form->get('client_h')->getData(),
            'table_x' => $form->get('table_x')->getData(),
            'table_y' => $form->get('table_y')->getData(),
            'table_w' => $form->get('table_w')->getData(),
            'table_h' => $form->get('table_h')->getData(),
            'totals_x' => $form->get('totals_x')->getData(),
            'totals_y' => $form->get('totals_y')->getData(),
            'totals_w' => $form->get('totals_w')->getData(),
            'totals_h' => $form->get('totals_h')->getData(),
            'footer_x' => $form->get('footer_x')->getData(),
            'footer_y' => $form->get('footer_y')->getData(),
            'footer_w' => $form->get('footer_w')->getData(),
            'footer_h' => $form->get('footer_h')->getData(),
            'signature_x' => $form->get('signature_x')->getData(),
            'signature_y' => $form->get('signature_y')->getData(),
            'signature_w' => $form->get('signature_w')->getData(),
            'signature_h' => $form->get('signature_h')->getData(),
            'header_title_font_size' => $form->get('header_title_font_size')->getData(),
            'font_size' => $form->get('font_size')->getData(),
            'line_height' => $form->get('line_height')->getData(),
        ]);
    }
}
