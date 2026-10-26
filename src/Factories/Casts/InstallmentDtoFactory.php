<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\InstallmentDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class InstallmentDtoFactory extends Factory
{
    protected $model = InstallmentDto::class;

    public function definition(): array
    {
        return [
            'period' => $this->faker->numberBetween(3, 10),
        ];
    }
}
