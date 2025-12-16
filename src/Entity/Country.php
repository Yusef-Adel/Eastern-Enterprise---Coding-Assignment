<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'countries')]
#[ORM\Index(name: 'idx_name', columns: ['name'])]
#[ORM\Index(name: 'idx_region', columns: ['region'])]
class Country
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $subRegion = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $demonym = null;

    #[ORM\Column(type: 'integer', options:  ['unsigned' => true, 'default' => 0])]
    private int $population = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $independent = false;

    #[ORM\Column(type: 'string', length: 500, nullable:  true)]
    private ?string $flag = null;

    #[ORM\Embedded(class:  Currency::class)]
    private Currency $currency;

    #[ORM\Column(type:  'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type:  'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->currency = new Currency();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // === GETTERS ===

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getSubRegion(): ?string
    {
        return $this->subRegion;
    }

    public function getDemonym(): ?string
    {
        return $this->demonym;
    }

    public function getPopulation(): int
    {
        return $this->population;
    }

    public function isIndependent(): bool
    {
        return $this->independent;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // === SETTERS ===

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updateTimestamp();
        return $this;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        $this->updateTimestamp();
        return $this;
    }

    public function setSubRegion(?string $subRegion): self
    {
        $this->subRegion = $subRegion;
        $this->updateTimestamp();
        return $this;
    }

    public function setDemonym(?string $demonym): self
    {
        $this->demonym = $demonym;
        $this->updateTimestamp();
        return $this;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;
        $this->updateTimestamp();
        return $this;
    }

    public function setIndependent(bool $independent): self
    {
        $this->independent = $independent;
        $this->updateTimestamp();
        return $this;
    }

    public function setFlag(?string $flag): self
    {
        $this->flag = $flag;
        $this->updateTimestamp();
        return $this;
    }

    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;
        $this->updateTimestamp();
        return $this;
    }

    // === HELPER METHODS ===

    private function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Convert entity to array representation for API responses
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'region' => $this->region,
            'subRegion' => $this->subRegion,
            'demonym' => $this->demonym,
            'population' => $this->population,
            'independent' => $this->independent,
            'flag' => $this->flag,
            'currency' => $this->currency->toArray(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}