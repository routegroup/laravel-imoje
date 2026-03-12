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
            'urlSuccess' => rtrim((string) (env('IMOJE_RETURN_URL') ?: 'https://example.com/success'), '/'),
            'urlFailure' => rtrim((string) (env('IMOJE_RETURN_URL') ?: 'https://example.com/failure'), '/'),
            'urlReturn' => rtrim((string) (env('IMOJE_RETURN_URL') ?: 'https://example.com/return'), '/'),
            'orderDescription' => $this->faker->sentence,
        ]);
    }
}
