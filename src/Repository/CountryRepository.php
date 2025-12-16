<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function findByUuid(string $uuid): ?Country
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function upsert(Country $country): void
    {
        $existingCountry = $this->findByUuid($country->getUuid());
        
        if ($existingCountry) {
            // Update existing
            $existingCountry->setName($country->getName())
                ->setRegion($country->getRegion())
                ->setSubRegion($country->getSubRegion())
                ->setDemonym($country->getDemonym())
                ->setPopulation($country->getPopulation())
                ->setIndependent($country->isIndependent())
                ->setFlag($country->getFlag())
                ->setCurrency($country->getCurrency());
            
            $this->getEntityManager()->persist($existingCountry);
        } else {
            // Insert new
            $this->getEntityManager()->persist($country);
        }
        
        $this->getEntityManager()->flush();
    }

    public function deleteAll(): void
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function findAll(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }

    /**
     * Find countries with filters and pagination
     */
    public function findWithFilters(
        ? string $region = null,
        ?bool $independent = null,
        int $page = 1,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        if ($region !== null) {
            $qb->andWhere('c.region = :region')
               ->setParameter('region', $region);
        }

        if ($independent !== null) {
            $qb->andWhere('c.independent = :independent')
               ->setParameter('independent', $independent);
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count countries with filters
     */
    public function countWithFilters(
        ?string $region = null,
        ?bool $independent = null
    ): int {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c. uuid)');

        if ($region !== null) {
            $qb->andWhere('c.region = :region')
               ->setParameter('region', $region);
        }

        if ($independent !== null) {
            $qb->andWhere('c.independent = :independent')
               ->setParameter('independent', $independent);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Delete a country by entity
     */
    public function delete(Country $country): void
    {
        $this->getEntityManager()->remove($country);
        $this->getEntityManager()->flush();
    }
}