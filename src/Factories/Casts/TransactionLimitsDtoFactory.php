<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionLimitDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionLimitsDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class TransactionLimitsDtoFactory extends Factory
{
    protected $model = TransactionLimitsDto::class;

    public function definition(): array
    {
        $min = $this->faker->numberBetween(1, 1000);

        return [
            'minTransaction' => TransactionLimitDto::factory()->make(['value' => $min])->toArray(),
            'maxTransaction' => TransactionLimitDto::factory()
                ->make(['value' => $this->faker->numberBetween($min + 1, 99999999)])->toArray(),
        ];
    }
}
