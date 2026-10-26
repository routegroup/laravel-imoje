<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\CartDiscountDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartItemDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\CartItemState;
use Routegroup\Imoje\Payment\Types\LeaseTaxStake;

class CartItemDtoFactory extends Factory
{
    protected $model = CartItemDto::class;

    public function definition(): array
    {
        $stake = $this->faker->randomElement(LeaseTaxStake::cases());
        // Net, in grosze, around the 1000 PLN the leasing floor asks for.
        $amount = $this->faker->numberBetween(1000, 20000) * 100;

        return [
            'id' => (string) $this->faker->unique()->numberBetween(1, 99999),
            'categoryId' => $this->faker->slug(2),
            'name' => $this->faker->words(3, true),
            'amount' => $amount,
            'tax' => (int) round($amount * $this->rate($stake) / 100),
            'taxStake' => $stake,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit' => $this->faker->randomElement(['szt', 'kpl', 'kg', 'm']),
            'url' => $this->faker->url,
            'state' => $this->faker->randomElement(CartItemState::cases()),
            'discount' => CartDiscountDto::factory()->make()->toArray(),
        ];
    }

    /** A non-numeric basis carries no percentage, so it carries no tax either. */
    private function rate(LeaseTaxStake $stake): int
    {
        return is_numeric($stake->value) ? (int) $stake->value : 0;
    }
}
