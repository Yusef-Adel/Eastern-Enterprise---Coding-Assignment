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
            $this->logger->info('Fetching countries from REST Countries API');

            // Specify fields to avoid 400 error
            $url = self::API_BASE_URL . '/all?fields=name,cca2,cca3,region,subregion,population,independent,flags,currencies,demonyms';

            $response = $this->httpClient->request(
                'GET',
                $url,
                [
                    'timeout' => self::TIMEOUT,
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => 'Symfony-Country-API/1.0',
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $body = $response->getContent(false);
                
                $this->logger->error('REST Countries API error', [
                    'status_code' => $statusCode,
                    'response' => $body
                ]);

                throw new \RuntimeException(
                    "REST Countries API returned status code {$statusCode}:  {$body}"
                );
            }

            $data = $response->toArray();

            $this->logger->info('Successfully fetched countries from REST Countries API', [
                'count' => count($data)
            ]);

            return $data;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error while fetching countries', [
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException(
                'Failed to fetch countries from REST Countries API: ' .  $e->getMessage(),
                0,
                $e
            );
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            $this->logger->error('HTTP error while fetching countries', [
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