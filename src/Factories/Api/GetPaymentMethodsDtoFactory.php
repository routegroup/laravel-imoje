<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Api;

use Routegroup\Imoje\Payment\DTO\Api\GetPaymentMethodsDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;

class GetPaymentMethodsDtoFactory extends Factory
{
    protected $model = GetPaymentMethodsDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100) * 100,
            'currency' => Currency::PLN,
        ];
    }
}
