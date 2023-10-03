<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CustomerDtoFactory extends Factory
{
    protected $model = CustomerDto::class;

    public function definition(): array
    {
        return [
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->numerify('#########'),
        ];
    }

    public function asCard(): static
    {
        return $this->state([
            'cid' => $this->faker->numerify('##############'),
            'company' => $this->faker->company,
        ]);
    }
}
