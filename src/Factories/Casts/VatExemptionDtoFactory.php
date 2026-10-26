<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\VatExemptionDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\VatExemptionType;

class VatExemptionDtoFactory extends Factory
{
    protected $model = VatExemptionDto::class;

    public function definition(): array
    {
        return [
            'type' => VatExemptionType::TUTORING,
        ];
    }

    public function other(): static
    {
        return $this->state(fn (): array => [
            'type' => VatExemptionType::OTHER,
            'text' => 'Zwolnienie na innej podstawie',
        ]);
    }
}
