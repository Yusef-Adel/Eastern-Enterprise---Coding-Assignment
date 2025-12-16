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
}