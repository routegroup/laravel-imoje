<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentMethodChannelDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentMethodGroupDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class PaymentMethodGroupDtoFactory extends Factory
{
    protected $model = PaymentMethodGroupDto::class;

    public function definition(): array
    {
        return [
            'label' => $this->faker->randomElement(['Płatność BLIK', 'Przelew tradycyjny', 'Karta']),
            'isOnline' => $this->faker->boolean(80),
            'priority' => $this->faker->numberBetween(1, 10),
            'image' => [['png' => 'https://cdn.pay.ing.pl/img/pay/blik.png']],
            'channels' => array_map(
                static fn (): array => PaymentMethodChannelDto::factory()->make()->toArray(),
                range(1, $this->faker->numberBetween(1, 3)),
            ),
        ];
    }
}
