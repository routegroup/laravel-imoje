<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\CartItemDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\CartItemState;
use Routegroup\Imoje\Payment\Types\LeaseTaxStake;

class CartItemDtoFactory extends Factory
{
    protected $model = CartItemDto::class;

    /** The example from the ING Lease Now chapter. */
    public function definition(): array
    {
        return [
            'id' => '1',
            'categoryId' => 'category123',
            'name' => 'Product name',
            'amount' => 500000,
            'tax' => 115000,
            'taxStake' => LeaseTaxStake::RATE_23,
            'quantity' => 1,
            'unit' => 'szt',
            'url' => 'https://product.url',
            'state' => CartItemState::NEW,
            'discount' => ['amount' => 100, 'tax' => 23],
        ];
    }
}
