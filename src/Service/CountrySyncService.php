<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CountryRepository;
use Psr\Log\LoggerInterface;

class CountrySyncService
{
    public function __construct(
        private readonly RestCountriesClient $restCountriesClient,
        private readonly CountryDataMapper $countryDataMapper,
        private readonly CountryRepository $countryRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Sync countries from REST Countries API to database
     *
     * @return int Number of countries synced
     * @throws \RuntimeException
     */
    public function syncCountries(): int
    {
        $this->logger->info('Starting country sync from REST Countries API');

        try {
            // 1. Fetch data from API
            $countriesData = $this->restCountriesClient->fetchAllCountries();
            $totalCount = count($countriesData);

            $this->logger->info("Fetched {$totalCount} countries from API");

            // 2. Process each country
            $synced = 0;
            $failed = 0;

            foreach ($countriesData as $countryData) {
                try {
                    // Map API data to entity
                    $country = $this->countryDataMapper->mapApiDataToEntity($countryData);

                    // Save to database (upsert)
                    $this->countryRepository->upsert($country);

                    $synced++;

                } catch (\Exception $e) {
                    $failed++;
                    $countryName = $countryData['name']['common'] ?? 'Unknown';
                    
                    $this->logger->error('Failed to sync country', [
                        'country' => $countryName,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->info('Country sync completed', [
                'total' => $totalCount,
                'synced' => $synced,
                'failed' => $failed
            ]);

            return $synced;

        } catch (\Exception $e) {
            $this->logger->error('Country sync failed', [
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException(
                'Failed to sync countries: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}