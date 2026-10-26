<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\CartDiscountDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CartDiscountDtoFactory extends Factory
{
    protected $model = CartDiscountDto::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(1, 50) * 100;

        return [
            'amount' => $amount,
            'tax' => (int) round($amount * 0.23),
        ];
    }
}
