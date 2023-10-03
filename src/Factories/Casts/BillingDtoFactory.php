<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\BillingDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class BillingDtoFactory extends Factory
{
    protected $model = BillingDto::class;

    public function definition(): array
    {
        return [
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'company' => $this->faker->company,
            'street' => $this->faker->streetName,
            'city' => $this->faker->city,
            'region' => $this->faker->state,
            'postalCode' => $this->faker->postcode,
            'countryCodeAlpha2' => $this->faker->countryCode,
        ];
    }
}
