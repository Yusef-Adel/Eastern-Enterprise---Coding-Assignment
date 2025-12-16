<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Country;
use App\Entity\Currency;
use Symfony\Component\Uid\Uuid;

class CountryDataMapper
{
    /**
     * Map API data to Country entity
     */
    public function mapApiDataToEntity(array $apiData): Country
    {
        $country = new Country();

        // Generate deterministic UUID from country code
        $countryCode = $apiData['cca3'] ?? $apiData['cca2'] ?? uniqid();
        $uuid = $this->generateUuidFromCountryCode($countryCode);
        $country->setUuid($uuid);

        // Map basic fields
        $country->setName($apiData['name']['common'] ?? 'Unknown');
        $country->setRegion($apiData['region'] ?? null);
        $country->setSubRegion($apiData['subregion'] ??  null);
        $country->setDemonym($this->extractDemonym($apiData));
        $country->setPopulation($apiData['population'] ??  0);
        $country->setIndependent($apiData['independent'] ?? false);
        $country->setFlag($this->extractFlag($apiData));
        $country->setCurrency($this->extractCurrency($apiData));

        return $country;
    }

    /**
     * Generate UUID v5 from country code for deterministic IDs
     */
    private function generateUuidFromCountryCode(string $countryCode): string
    {
        $namespace = Uuid::fromString(Uuid::NAMESPACE_DNS);
        return Uuid::v5($namespace, 'restcountries: ' . $countryCode)->toRfc4122();
    }

    /**
     * Extract demonym from API data
     */
    private function extractDemonym(array $apiData): ?string
    {
        // Try male demonym first
        if (isset($apiData['demonyms']['eng']['m'])) {
            return $apiData['demonyms']['eng']['m'];
        }

        // Try female demonym
        if (isset($apiData['demonyms']['eng']['f'])) {
            return $apiData['demonyms']['eng']['f'];
        }

        return null;
    }

    /**
     * Extract currency from API data (first currency found)
     */
    private function extractCurrency(array $apiData): Currency
    {
        if (!isset($apiData['currencies']) || empty($apiData['currencies'])) {
            return new Currency(null, null);
        }

        // Get first currency
        $currencyData = reset($apiData['currencies']);

        $name = $currencyData['name'] ?? null;
        $symbol = $currencyData['symbol'] ?? null;

        return new Currency($name, $symbol);
    }

    /**
     * Extract flag URL from API data (prefer SVG over PNG)
     */
    private function extractFlag(array $apiData): ?string
    {
        // Prefer SVG
        if (isset($apiData['flags']['svg'])) {
            return $apiData['flags']['svg'];
        }

        // Fallback to PNG
        if (isset($apiData['flags']['png'])) {
            return $apiData['flags']['png'];
        }

        return null;
    }
}