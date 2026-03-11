<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Entity\Payment;
use App\Entity\PaymentFacture;
use App\Repository\DocumentCounterRepository;
use App\Repository\FactureRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment', name: 'app_payment_')]
#[IsGranted('PAYMENT_VIEW')]
class PaymentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PaymentRepository $paymentRepository): Response
    {
        $methodFilter = (string) $request->query->get('method', 'all');
        $dateFrom = (string) $request->query->get('date_from', '');
        $dateTo = (string) $request->query->get('date_to', '');

        $queryBuilder = $paymentRepository->createQueryBuilder('p')
            ->leftJoin('p.paymentFactures', 'pf')
            ->leftJoin('pf.facture', 'f')
            ->addSelect('pf', 'f')
            ->orderBy('p.payment_date', 'DESC')
            ->addOrderBy('p.id', 'DESC');

        if ($methodFilter !== 'all') {
            $queryBuilder
                ->andWhere('p.method = :method')
                ->setParameter('method', $methodFilter);
        }

        if ($dateFrom !== '') {
            $queryBuilder
                ->andWhere('p.payment_date >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom . ' 00:00:00'));
        }

        if ($dateTo !== '') {
            $queryBuilder
                ->andWhere('p.payment_date <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        return $this->render('payment/index.html.twig', [
            'payments' => $queryBuilder->getQuery()->getResult(),
            'methodFilter' => $methodFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'methodChoices' => [
                'CASH' => 'Especes',
                'CHEQUE' => 'Cheque',
                'VIREMENT' => 'Virement',
                'CARTE' => 'Carte',
            ],
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('PAYMENT_CREATE')]
    public function new(
        Request $request,
        FactureRepository $factureRepository,
        DocumentCounterRepository $documentCounterRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $eligibleInvoices = $factureRepository->createQueryBuilder('f')
            ->leftJoin('f.paymentFactures', 'pf')
            ->leftJoin('f.bonLivraisons', 'bl')
            ->leftJoin('bl.bon_commande', 'bc')
            ->leftJoin('bc.client', 'c')
            ->addSelect('pf', 'bl', 'bc', 'c')
            ->andWhere('f.status IN (:statuses)')
            ->setParameter('statuses', ['UNPAID', 'PARTIALLY_PAID'])
            ->orderBy('f.created_at', 'ASC')
            ->getQuery()
            ->getResult();

        if ($request->isMethod('POST')) {
            $paymentDateRaw = (string) $request->request->get('payment_date', '');
            $method = (string) $request->request->get('method', '');
            $externalReference = trim((string) $request->request->get('external_reference', ''));
            $amount = (float) $request->request->get('amount', 0);
            $rawAllocations = $request->request->all('allocations');
            $validMethods = ['CASH', 'CHEQUE', 'VIREMENT', 'CARTE'];

            if ($paymentDateRaw === '') {
                $this->addFlash('error', 'La date de paiement est obligatoire.');
                return $this->render('payment/new.html.twig', [
                    'available_invoices' => $eligibleInvoices,
                    'methodChoices' => $validMethods,
                ]);
            }

            if (!in_array($method, $validMethods, true)) {
                $this->addFlash('error', 'Methode de paiement invalide.');
                return $this->render('payment/new.html.twig', [
                    'available_invoices' => $eligibleInvoices,
                    'methodChoices' => $validMethods,
                ]);
            }

            if ($amount <= 0) {
                $this->addFlash('error', 'Le montant du paiement doit etre superieur a 0.');
                return $this->render('payment/new.html.twig', [
                    'available_invoices' => $eligibleInvoices,
                    'methodChoices' => $validMethods,
                ]);
            }

            $allocations = [];
            foreach ($rawAllocations as $invoiceId => $allocatedRaw) {
                $id = (int) $invoiceId;
                $allocated = (float) $allocatedRaw;
                if ($id > 0 && $allocated > 0) {
                    $allocations[$id] = $allocated;
                }
            }

            if ($allocations === []) {
                $this->addFlash('error', 'Veuillez allouer le paiement a au moins une facture.');
                return $this->render('payment/new.html.twig', [
                    'available_invoices' => $eligibleInvoices,
                    'methodChoices' => $validMethods,
                ]);
            }

            $invoiceIds = array_keys($allocations);
            $invoices = $factureRepository->createQueryBuilder('f')
                ->leftJoin('f.paymentFactures', 'pf')
                ->addSelect('pf')
                ->andWhere('f.id IN (:ids)')
                ->setParameter('ids', $invoiceIds)
                ->getQuery()
                ->getResult();

            if (count($invoices) !== count($invoiceIds)) {
                $this->addFlash('error', 'Une ou plusieurs factures selectionnees sont invalides.');
                return $this->render('payment/new.html.twig', [
                    'available_invoices' => $eligibleInvoices,
                    'methodChoices' => $validMethods,
                ]);
            }

            $invoiceMap = [];
            foreach ($invoices as $invoice) {
                $invoiceMap[$invoice->getId()] = $invoice;
            }

            $sumAllocated = 0.0;
            foreach ($allocations as $invoiceId => $allocated) {
                /** @var Facture|null $invoice */
                $invoice = $invoiceMap[$invoiceId] ?? null;
                if ($invoice === null) {
                    $this->addFlash('error', 'Facture introuvable pour allocation.');
                    return $this->render('payment/new.html.twig', [
                        'available_invoices' => $eligibleInvoices,
                        'methodChoices' => $validMethods,
                    ]);
                }

                if (!in_array($invoice->getStatus(), ['UNPAID', 'PARTIALLY_PAID'], true)) {
                    $this->addFlash('error', sprintf('La facture %s ne peut pas recevoir de paiement.', $invoice->getReference()));
                    return $this->render('payment/new.html.twig', [
                        'available_invoices' => $eligibleInvoices,
                        'methodChoices' => $validMethods,
                    ]);
                }

                $remaining = (float) $invoice->getRemainingAmount();
                if ($allocated - $remaining > 0.0001) {
                    $this->addFlash('error', sprintf('Le montant alloue pour %s depasse le reste a payer (%.3f TND).', $invoice->getReference(), $remaining));
                    return $this->render('payment/new.html.twig', [
                        'available_invoices' => $eligibleInvoices,
                        'methodChoices' => $validMethods,
                    ]);
                }

                $sumAllocated += $allocated;
            }

            if (abs($sumAllocated - $amount) > 0.0001) {
                $this->addFlash('error', sprintf('Le montant total alloue (%.3f) doit etre egal au montant du paiement (%.3f).', $sumAllocated, $amount));
                return $this->render('payment/new.html.twig', [
                    'available_invoices' => $eligibleInvoices,
                    'methodChoices' => $validMethods,
                ]);
            }

            $payment = new Payment();
            $payment->setPaymentDate(new \DateTime($paymentDateRaw));
            $payment->setMethod($method);
            $payment->setReference($this->generatePaymentReference($documentCounterRepository, $entityManager));
            $payment->setExternalReference($externalReference === '' ? null : $externalReference);
            $payment->setAmount(number_format($amount, 3, '.', ''));
            $payment->setCreatedBy($this->getUser());
            $entityManager->persist($payment);

            foreach ($allocations as $invoiceId => $allocated) {
                /** @var Facture $invoice */
                $invoice = $invoiceMap[$invoiceId];

                $allocation = new PaymentFacture();
                $allocation->setPayment($payment);
                $allocation->setFacture($invoice);
                $allocation->setAmountAllocated(number_format($allocated, 3, '.', ''));

                $payment->addPaymentFacture($allocation);
                $invoice->addPaymentFacture($allocation);
                $entityManager->persist($allocation);

                $this->refreshInvoiceStatus($invoice);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Paiement enregistre avec succes.');
            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        return $this->render('payment/new.html.twig', [
            'available_invoices' => $eligibleInvoices,
            'methodChoices' => ['CASH', 'CHEQUE', 'VIREMENT', 'CARTE'],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    private function refreshInvoiceStatus(Facture $invoice): void
    {
        if ($invoice->getStatus() === 'CANCELLED') {
            return;
        }

        $remaining = (float) $invoice->getRemainingAmount();
        if ($remaining <= 0.0001) {
            $invoice->setStatus('PAID');
            return;
        }

        $paid = (float) $invoice->getTotalPaid();
        if ($paid > 0.0001) {
            $invoice->setStatus('PARTIALLY_PAID');
            return;
        }

        $invoice->setStatus('UNPAID');
    }

    private function generatePaymentReference(
        DocumentCounterRepository $documentCounterRepository,
        EntityManagerInterface $entityManager
    ): string {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $counter = $documentCounterRepository->getOrCreateCounter('PAYMENT', $year);
        $nextNumber = $counter->getNextNumber();

        $entityManager->persist($counter);

        return sprintf('PAY-%d-%04d', $year, $nextNumber);
    }
}
