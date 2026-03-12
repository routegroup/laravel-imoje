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
            'returnUrl' => rtrim((string) (env('IMOJE_RETURN_URL') ?: 'https://example.com/return'), '/'),
            'successReturnUrl' => rtrim((string) (env('IMOJE_RETURN_URL') ?: 'https://example.com/success'), '/'),
            'failureReturnUrl' => rtrim((string) (env('IMOJE_RETURN_URL') ?: 'https://example.com/failure'), '/'),
        ];
    }
}
