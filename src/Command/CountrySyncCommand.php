<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\CountryRepository;
use App\Service\CountrySyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:country:sync',
    description: 'Sync countries from REST Countries API to database'
)]
class CountrySyncCommand extends Command
{
    public function __construct(
        private readonly CountrySyncService $countrySyncService,
        private readonly CountryRepository $countryRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Sync countries from REST Countries API to database')
            ->setHelp(
                'This command fetches country data from REST Countries API ' .
                'and syncs it to the local database.  Use --reset to delete ' .
                'all existing countries before syncing.'
            )
            ->addOption(
                'reset',
                null,
                InputOption::VALUE_NONE,
                'Delete all existing countries before syncing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Country Sync Command');

        try {
            // Check if reset option is provided
            if ($input->getOption('reset')) {
                $io->warning('Deleting all existing countries.. .');
                $this->countryRepository->deleteAll();
                $io->success('All countries deleted successfully');
            }

            // Start sync
            $io->section('Fetching countries from REST Countries API.. .');

            $syncedCount = $this->countrySyncService->syncCountries();

            $io->success("Successfully synced {$syncedCount} countries!");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to sync countries: ' . $e->getMessage());
            
            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), 'ERROR', 'fg=white;bg=red', ' ', true);
            }

            return Command::FAILURE;
        }
    }
}