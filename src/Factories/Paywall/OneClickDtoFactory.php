<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Paywall;

use Routegroup\Imoje\Payment\DTO\Paywall\OneClickDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;

class OneClickDtoFactory extends Factory
{
    protected $model = OneClickDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100000) * 100,
            'currency' => Currency::PLN,
            'orderId' => $this->faker->unique()->uuid,
            'customerId' => $this->faker->numerify,
            'customerFirstName' => $this->faker->firstName,
            'customerLastName' => $this->faker->lastName,
            'customerEmail' => $this->faker->email,
        ];
    }

    public function withOptional(): static
    {
        return $this->state([
            'customerPhone' => $this->faker->numerify('#########'),
            'orderDescription' => $this->faker->sentence,
            'urlSuccess' => 'https://imoje.requestcatcher.com/',
            'urlFailure' => 'https://imoje.requestcatcher.com/',
            'urlReturn' => 'https://imoje.requestcatcher.com/',
            'urlCancel' => 'https://imoje.requestcatcher.com/',
        ]);
    }
}
