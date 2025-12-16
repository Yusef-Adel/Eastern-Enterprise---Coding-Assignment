<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Currency
{
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $symbol = null;

    public function __construct(?string $name = null, ?string $symbol = null)
    {
        $this->name = $name;
        $this->symbol = $symbol;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'symbol' => $this->symbol,
        ];
    }
}