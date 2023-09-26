<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Requests;

use Routegroup\Imoje\Payment\DTO\Requests\ChargeProfileRequestDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;

class ChargeProfileRequestDtoFactory extends Factory
{
    protected $model = ChargeProfileRequestDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100) * 100,
            'currency' => $this->faker->randomElement(Currency::cases()),
        ];
    }
}
