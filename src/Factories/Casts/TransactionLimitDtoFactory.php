<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionLimitDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class TransactionLimitDtoFactory extends Factory
{
    protected $model = TransactionLimitDto::class;

    public function definition(): array
    {
        return ['type' => 'number', 'value' => $this->faker->numberBetween(1, 99999999)];
    }
}
