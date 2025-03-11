<?php

namespace App\Command;

use App\Model\Book;
use App\Repository\AuctionGeneratedFieldsRepository;
use App\Repository\AuctionInterface;
use App\Repository\AuctionJsonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fixtures:generate:em',
    description: 'Generate an amount of fixture data through the entity manager',
)]
class FixturesGenerateEmCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuctionJsonRepository $jsonRepository,
        private readonly AuctionGeneratedFieldsRepository $generatedFieldsRepository,
        private readonly bool $isDebug,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'Number of rows to generate for each type of data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->isDebug) {
            $io->warning('Running in debug mode means we might run out of memory. If that happens, re-run this command with --env=prod');
        }
        $this->jsonRepository->truncate();
        $this->generatedFieldsRepository->truncate();

        $amount = $input->getArgument('amount');

        $start = hrtime(true);
        $this->generateJson($amount, $this->jsonRepository);
        $duration = (int) ((hrtime(true) - $start) / 1000 / 1000);
        $io->success("Generated $amount plain JSON fixtures in $duration milliseconds");

        $start = hrtime(true);
        $this->generateJson($amount, $this->generatedFieldsRepository);
        $duration = (int) ((hrtime(true) - $start) / 1000 / 1000);
        $io->success("Generated $amount JSON fixtures with generated fields in $duration milliseconds");

        return Command::SUCCESS;
    }

    private function generateJson(int $amount, AuctionInterface $repository): void
    {
        $digits = strlen($amount);

        // This approach is naive. I want to test querying and therefor did not care about optimizing this.
        // If you regularly need to import large amounts of rows, you should work with prepared statements,
        // and also try to avoid creating models and going through doctrine ORM, as it adds significant overhead.
        for ($i = 1; $i <= $amount; $i++) {
            $number = str_pad($i, $digits, '0', STR_PAD_LEFT);

            $repository->createAuction(new Book(
                "Title $number",
                new \DateTimeImmutable(),
                new \DateTimeImmutable('+ 24 hours'),
                "Description $number",
                "Author ".$i % 1000,
                "Genre ".$i % 15
            ));

            // without clearing the entity manager periodically, inserting gets extremely slow when exceeding a few thousand items
            // before we clear, we need to flush. i am surprised that frequent flushing is much more performant than going with larger batches.
            if ($i % 5) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
    }
}
