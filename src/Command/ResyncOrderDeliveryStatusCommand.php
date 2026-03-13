<?php

namespace App\Command;

use App\Repository\BonCommandeRepository;
use App\Service\OrderDeliveryStatusManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:orders:resync-delivery-statuses', description: 'Recompute order statuses from validated delivery notes.')]
class ResyncOrderDeliveryStatusCommand extends Command
{
    public function __construct(
        private readonly BonCommandeRepository $bonCommandeRepository,
        private readonly OrderDeliveryStatusManager $orderDeliveryStatusManager,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $updatedCount = 0;

        foreach ($this->bonCommandeRepository->findAll() as $order) {
            if ($this->orderDeliveryStatusManager->refresh($order)) {
                $updatedCount++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d order(s) resynchronized.', $updatedCount));

        return Command::SUCCESS;
    }
}