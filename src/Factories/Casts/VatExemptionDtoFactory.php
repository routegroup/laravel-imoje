<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\VatExemptionDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\VatExemptionType;

class VatExemptionDtoFactory extends Factory
{
    protected $model = VatExemptionDto::class;

    /** Every basis but OTHER, which needs a description and gets its own state. */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(array_filter(
                VatExemptionType::cases(),
                static fn (VatExemptionType $type): bool => $type !== VatExemptionType::OTHER,
            )),
        ];
    }

    public function other(): static
    {
        return $this->state(fn (): array => [
            'type' => VatExemptionType::OTHER,
            'text' => 'Zwolnienie na podstawie '.$this->faker->numerify('art. ###'),
        ]);
    }
}
