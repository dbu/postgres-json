<?php

namespace App\Command;

use App\Repository\AuctionJsonRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:query',
    description: 'Run some queries and profile them - the interesting part is looking at the repository code',
)]
class QueryExamplesCommand extends Command
{
    public function __construct(
        private readonly AuctionJsonRepository $jsonRepository,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $start = hrtime(true);
        $io->info($this->jsonRepository->countAuthor('Author 1').' matches');
        $duration = (int)((hrtime(true) - $start) / 1000 / 1000);
        $io->success("Took $duration milliseconds to query JSON repository");

        return Command::SUCCESS;
    }
}
