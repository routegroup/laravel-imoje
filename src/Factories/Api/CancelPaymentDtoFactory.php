<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Api;

use Routegroup\Imoje\Payment\DTO\Api\CancelPaymentDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CancelPaymentDtoFactory extends Factory
{
    protected $model = CancelPaymentDto::class;

    public function definition(): array
    {
        return [
            'paymentId' => $this->faker->unique()->uuid,
        ];
    }
}
