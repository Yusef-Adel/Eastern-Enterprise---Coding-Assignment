<?php

namespace App\Command;

use App\Entity\Country;
use App\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name:  'app:country:sync',
    description: 'Sync countries from REST Countries API and reset modified data',
)]
class CountrySyncCommand extends Command
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Syncing countries from REST Countries API');

        try {
            $io->text('Fetching countries from REST Countries API.. .');
            
            // Fetch all countries from REST Countries API with specific fields
            $response = $this->httpClient->request(
                'GET', 
                'https://restcountries.com/v3.1/all?fields=name,region,subregion,demonyms,population,independent,flags,currencies',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 30,
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                $io->error("API returned status code:  {$statusCode}");
                $io->text("Response: " . $response->getContent(false));
                return Command::FAILURE;
            }

            $apiCountries = $response->toArray();
            $io->success("Fetched " . count($apiCountries) . " countries from API");

            $syncedCount = 0;
            $createdCount = 0;
            $updatedCount = 0;

            // Track which countries exist in the API
            $apiCountryNames = [];

            foreach ($apiCountries as $countryData) {
                $countryName = $countryData['name']['common'] ?? null;
                
                if (! $countryName) {
                    continue;
                }

                $apiCountryNames[] = $countryName;

                // Find existing country by name
                $country = $this->entityManager->getRepository(Country::class)
                    ->findOneBy(['name' => $countryName]);

                $isNew = false;
                if (!$country) {
                    $country = new Country();
                    $isNew = true;
                    $io->text("✓ Creating:  {$countryName}");
                } else {
                    $io->text("↻ Updating: {$countryName}");
                }

                // Update country data from API
                $country->setName($countryName);
                $country->setRegion($countryData['region'] ??  '');
                $country->setSubRegion($countryData['subregion'] ?? null);
                $country->setDemonym($countryData['demonyms']['eng']['m'] ??  null);
                $country->setPopulation($countryData['population'] ?? 0);
                $country->setIndependent($countryData['independent'] ?? false);
                $country->setFlag($countryData['flags']['svg'] ?? null);

                // Update currency
                $currencies = $countryData['currencies'] ?? [];
                if (!empty($currencies)) {
                    $currencyCode = array_key_first($currencies);
                    $currencyData = $currencies[$currencyCode];
                    
                    $currency = $country->getCurrency() ?? new Currency();
                    $currency->setName($currencyData['name'] ?? '');
                    $currency->setSymbol($currencyData['symbol'] ?? '');
                    
                    $country->setCurrency($currency);
                }

                $this->entityManager->persist($country);
                
                if ($isNew) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
                
                $syncedCount++;

                // Flush every 50 countries to avoid memory issues
                if ($syncedCount % 50 === 0) {
                    $this->entityManager->flush();
                    $io->comment("Flushed {$syncedCount} countries.. .");
                }
            }

            // Final flush
            $this->entityManager->flush();
            $io->success("All countries persisted to database");

            // Delete countries that don't exist in REST Countries API anymore
            $io->section('Checking for countries to delete.. .');
            $allCountries = $this->entityManager->getRepository(Country::class)->findAll();
            $deletedCount = 0;

            foreach ($allCountries as $country) {
                if (!in_array($country->getName(), $apiCountryNames, true)) {
                    $io->warning("✗ Deleting: {$country->getName()} (not found in REST Countries API)");
                    $this->entityManager->remove($country);
                    $deletedCount++;
                }
            }

            $this->entityManager->flush();

            $io->newLine();
            $io->success([
                '🎉 Country sync completed successfully! ',
                '',
                "📊 Statistics:",
                "  • Created: {$createdCount}",
                "  • Updated: {$updatedCount}",
                "  • Deleted:  {$deletedCount}",
                "  • Total synced:  {$syncedCount}",
            ]);

            return Command:: SUCCESS;

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $io->error([
                'Network error while fetching from REST Countries API',
                'Error: ' . $e->getMessage(),
            ]);
            return Command:: FAILURE;
        } catch (\Exception $e) {
            $io->error([
                'Error syncing countries',
                'Error: ' . $e->getMessage(),
                'Trace: ' . $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}