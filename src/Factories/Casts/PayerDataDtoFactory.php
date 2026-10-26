<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\PayerDataDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class PayerDataDtoFactory extends Factory
{
    protected $model = PayerDataDto::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'account' => $this->faker->numerify(str_repeat('#', 26)),
        ];
    }
}
