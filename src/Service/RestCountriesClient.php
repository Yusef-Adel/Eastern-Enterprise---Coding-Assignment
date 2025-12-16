<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RestCountriesClient
{
    private const API_BASE_URL = 'https://restcountries.com/v3.1';
    private const TIMEOUT = 30;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Fetch all countries from REST Countries API
     *
     * @return array
     * @throws \RuntimeException
     */
    public function fetchAllCountries(): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                self::API_BASE_URL . '/all',
                [
                    'timeout' => self::TIMEOUT,
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new \RuntimeException(
                    "REST Countries API returned status code {$statusCode}"
                );
            }

            $data = $response->toArray();

            $this->logger->info('Successfully fetched countries from REST Countries API', [
                'count' => count($data)
            ]);

            return $data;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to fetch countries from REST Countries API', [
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException(
                'Failed to fetch countries from REST Countries API: ' . $e->getMessage(),
                0,
                $e
            );
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while fetching countries', [
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException(
                'Failed to fetch countries from REST Countries API:  ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}