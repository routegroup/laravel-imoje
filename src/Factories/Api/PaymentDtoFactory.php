<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Api;

use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;

class PaymentDtoFactory extends Factory
{
    protected $model = PaymentDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100) * 100,
            'currency' => Currency::PLN,
            'orderId' => $this->faker->uuid,
            'customer' => CustomerDto::factory(),
            'returnUrl' => 'https://imoje.requestcatcher.com',
            'successReturnUrl' => 'https://imoje.requestcatcher.com',
            'failureReturnUrl' => 'https://imoje.requestcatcher.com',
        ];
    }
}
