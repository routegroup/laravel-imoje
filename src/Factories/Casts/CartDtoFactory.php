<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\CartDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartItemDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class CartDtoFactory extends Factory
{
    protected $model = CartDto::class;

    public function definition(): array
    {
        return [
            'items' => array_map(
                static fn (): array => CartItemDto::factory()->make()->toArray(),
                range(1, $this->faker->numberBetween(1, 4)),
            ),
        ];
    }
}
