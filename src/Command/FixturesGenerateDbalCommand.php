<?php

namespace App\Command;

use App\Model\Book;
use App\Repository\AuctionGeneratedFieldsRepository;
use App\Repository\AuctionInterface;
use App\Repository\AuctionJsonbGinRepository;
use App\Repository\AuctionJsonbRepository;
use App\Repository\AuctionJsonRepository;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fixtures:generate:dbal',
    description: 'Generate an amount of fixture data using Doctrine dbal prepared statement',
)]
class FixturesGenerateDbalCommand extends Command
{
    public function __construct(
        private readonly AuctionJsonRepository $jsonRepository,
        private readonly AuctionJsonbRepository $jsonbRepository,
        private readonly AuctionJsonbGinRepository $jsonbGinRepository,
        private readonly AuctionGeneratedFieldsRepository $generatedFieldsRepository,
        private readonly bool $isDebug,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'Number of rows to generate for each type of data')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Batch insert a number of rows - on my machine 1000 to 1500 led to best performance', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->isDebug) {
            $io->warning('Running in debug mode means we might run out of memory. If that happens, re-run this command with --env=prod');
        }
        $this->jsonbRepository->truncate();
        $this->generatedFieldsRepository->truncate();
        $this->jsonRepository->truncate();

        $amount = $input->getArgument('amount');
        $batchSize = $input->getOption('batch-size');

        $start = hrtime(true);
        $this->generateJson($amount, $batchSize, $this->jsonRepository, $io);
        $duration = (int) ((hrtime(true) - $start) / 1000 / 1000);
        $io->success("Generated $amount plain JSON fixtures in $duration milliseconds");

        $start = hrtime(true);
        $this->generateJson($amount, $batchSize, $this->jsonbRepository, $io);
        $duration = (int) ((hrtime(true) - $start) / 1000 / 1000);
        $io->success("Generated $amount JSONB fixtures in $duration milliseconds");

        $start = hrtime(true);
        $this->generateJson($amount, $batchSize, $this->jsonbGinRepository, $io);
        $duration = (int) ((hrtime(true) - $start) / 1000 / 1000);
        $io->success("Generated $amount JSONB GIN fixtures in $duration milliseconds");

        $start = hrtime(true);
        $this->generateJson($amount, $batchSize, $this->generatedFieldsRepository, $io);
        $duration = (int) ((hrtime(true) - $start) / 1000 / 1000);
        $io->success("Generated $amount JSON fixtures with generated fields in $duration milliseconds");

        return Command::SUCCESS;
    }

    private function generateJson(int $amount, int $batchSize, AuctionInterface $repository, SymfonyStyle $io): void
    {
        $digits = strlen($amount);
        $statement = $repository->getInsertStatement($batchSize);

        $iteration = 0;
        for ($i = 1; $i <= $amount; $i++) {
            $number = str_pad($i, $digits, '0', STR_PAD_LEFT);

            if (!$repository instanceof AuctionGeneratedFieldsRepository) {
                $statement->bindValue(":title$iteration", "Title $number");
                $statement->bindValue(":startDate$iteration", new \DateTimeImmutable(), Type::getType('datetime_immutable'));
                $statement->bindValue(":endDate$iteration", new \DateTimeImmutable('+ 24 hours'), Type::getType('datetime_immutable'));
            }
            $statement->bindValue(":item$iteration", '{"type": "book", "genre": "Genre '.($i % 15).'", "title": "Title '.$i.'", "author": "Author '.($i % 1000).'", "endDate": "2024-01-01T00:07:00", "startDate": "2024-01-01T00:00:00", "description": "Description '.$i.'"}');

            if (++$iteration === $batchSize) {
                $statement->executeStatement();
                $iteration = 0;
                // looks like this when generating data.
                // if actual input is an array, we would use array_chunk and prepare with chunk size
                if ($amount - $i < $batchSize) {
                    $statement = $repository->getInsertStatement($amount - $i);
                }
            }
        }
        if ($iteration > 0) {
            $statement->executeStatement();
        }
    }
}
