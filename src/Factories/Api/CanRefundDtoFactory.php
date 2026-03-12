<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Api;

use Routegroup\Imoje\Payment\DTO\Api\CanRefundDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CanRefundDtoFactory extends Factory
{
    protected $model = CanRefundDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100) * 100,
        ];
    }
}
