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
        return [
            'amount' => 100,
            'tax' => 23,
        ];
    }
}
