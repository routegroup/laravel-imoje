<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Paywall;

use Routegroup\Imoje\Payment\DTO\Paywall\InstallmentsCalculatorDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class InstallmentsCalculatorDtoFactory extends Factory
{
    protected $model = InstallmentsCalculatorDto::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(
                InstallmentsCalculatorDto::MIN_AMOUNT,
                InstallmentsCalculatorDto::MAX_AMOUNT,
            ),
        ];
    }
}
