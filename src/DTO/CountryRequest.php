<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CountryRequest
{
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(max: 255)]
    public ? string $name = null;

    #[Assert\Length(max:  100)]
    public ?string $region = null;

    #[Assert\Length(max: 100)]
    public ?string $subRegion = null;

    #[Assert\Length(max:  100)]
    public ?string $demonym = null;

    #[Assert\PositiveOrZero]
    public ? int $population = 0;

    public ? bool $independent = false;

    #[Assert\Url]
    #[Assert\Length(max: 500)]
    public ?string $flag = null;

    public ?array $currency = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? null;
        $dto->region = $data['region'] ?? null;
        $dto->subRegion = $data['subRegion'] ?? null;
        $dto->demonym = $data['demonym'] ?? null;
        $dto->population = isset($data['population']) ? (int)$data['population'] : 0;
        $dto->independent = isset($data['independent']) ? (bool)$data['independent'] :  false;
        $dto->flag = $data['flag'] ?? null;
        $dto->currency = $data['currency'] ?? null;

        return $dto;
    }
}