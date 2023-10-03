<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Paywall;

use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;

class TransactionDtoFactory extends Factory
{
    protected $model = TransactionDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100000) * 100,
            'currency' => Currency::PLN,
            'orderId' => $this->faker->unique()->uuid,
            'customerFirstName' => $this->faker->firstName,
            'customerLastName' => $this->faker->lastName,
        ];
    }

    public function withOptional(): static
    {
        return $this->state([
            'customerEmail' => $this->faker->email,
            'customerPhone' => $this->faker->numerify('#########'),
            'urlSuccess' => 'https://imoje.requestcatcher.com/',
            'urlFailure' => 'https://imoje.requestcatcher.com/',
            'urlReturn' => 'https://imoje.requestcatcher.com/',
            'orderDescription' => $this->faker->sentence,
        ]);
    }
}
