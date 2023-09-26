<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Requests;

use Routegroup\Imoje\Payment\DTO\Requests\RefundRequestDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class RefundRequestDtoFactory extends Factory
{
    protected $model = RefundRequestDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(1, 100) * 100,
        ];
    }
}
