<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\DTO\CountryRequest;
use App\Entity\Country;
use App\Entity\Currency;
use App\Repository\CountryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/countries', name: 'api_v1_countries_')]
class CountryController extends AbstractController
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * List all countries with pagination and filters
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Get query parameters
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 10)));
        $region = $request->query->get('region');
        $independent = $request->query->has('independent') 
            ? filter_var($request->query->get('independent'), FILTER_VALIDATE_BOOLEAN)
            : null;

        // Fetch countries
        $countries = $this->countryRepository->findWithFilters(
            $region,
            $independent,
            $page,
            $limit
        );

        // Get total count
        $total = $this->countryRepository->countWithFilters($region, $independent);

        // Transform to array
        $data = array_map(fn(Country $country) => $country->toArray(), $countries);

        return $this->json([
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Get single country by UUID
     */
    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);

        if (!$country) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($country->toArray());
    }

    /**
     * Create new country
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Parse JSON body
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Create DTO and validate
        $dto = CountryRequest::fromArray($data);
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'details' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Create entity
        $country = new Country();
        $this->populateCountryFromDTO($country, $dto);

        // Save
        $this->countryRepository->upsert($country);

        return $this->json($country->toArray(), Response::HTTP_CREATED);
    }

    /**
     * Update existing country
     */
    #[Route('/{uuid}', name: 'update', methods: ['PATCH'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);

        if (!$country) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        // Parse JSON body
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], Response:: HTTP_BAD_REQUEST);
        }

        // Create DTO (fields are optional for update)
        $dto = CountryRequest::fromArray($data);

        // Validate only provided fields
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'details' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Update entity (only update provided fields)
        if (isset($data['name'])) {
            $country->setName($dto->name);
        }
        if (isset($data['region'])) {
            $country->setRegion($dto->region);
        }
        if (isset($data['subRegion'])) {
            $country->setSubRegion($dto->subRegion);
        }
        if (isset($data['demonym'])) {
            $country->setDemonym($dto->demonym);
        }
        if (isset($data['population'])) {
            $country->setPopulation($dto->population);
        }
        if (isset($data['independent'])) {
            $country->setIndependent($dto->independent);
        }
        if (isset($data['flag'])) {
            $country->setFlag($dto->flag);
        }
        if (isset($data['currency']) && is_array($data['currency'])) {
            $currency = new Currency(
                $data['currency']['name'] ?? null,
                $data['currency']['symbol'] ?? null
            );
            $country->setCurrency($currency);
        }

        // Save
        $this->countryRepository->upsert($country);

        return $this->json($country->toArray());
    }

    /**
     * Delete country
     */
    #[Route('/{uuid}', name:  'delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);

        if (!$country) {
            return $this->json(['error' => 'Country not found'], Response::HTTP_NOT_FOUND);
        }

        $this->countryRepository->delete($country);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Helper to populate Country entity from DTO
     */
    private function populateCountryFromDTO(Country $country, CountryRequest $dto): void
    {
        $country->setName($dto->name);
        $country->setRegion($dto->region);
        $country->setSubRegion($dto->subRegion);
        $country->setDemonym($dto->demonym);
        $country->setPopulation($dto->population);
        $country->setIndependent($dto->independent);
        $country->setFlag($dto->flag);

        if ($dto->currency !== null && is_array($dto->currency)) {
            $currency = new Currency(
                $dto->currency['name'] ?? null,
                $dto->currency['symbol'] ?? null
            );
            $country->setCurrency($currency);
        }
    }
}