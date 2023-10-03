<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\CardDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CardDtoFactory extends Factory
{
    protected $model = CardDto::class;

    public function definition(): array
    {
        $expirationDate = $this->faker->creditCardExpirationDate;

        return [
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'number' => $this->faker->creditCardNumber,
            'month' => $expirationDate->format('m'),
            'year' => $expirationDate->format('Y'),
            'cvv' => $this->faker->numerify('###'),
        ];
    }
}
