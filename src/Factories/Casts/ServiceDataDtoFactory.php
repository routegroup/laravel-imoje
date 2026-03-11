<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ServiceDataDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class ServiceDataDtoFactory extends Factory
{
    protected $model = ServiceDataDto::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'created' => 1735686000,
            'isActive' => true,
            'paymentMethods' => [
                [
                    'paymentMethod' => 'blik',
                    'paymentMethodCode' => 'blik',
                    'isActive' => true,
                    'isOnline' => true,
                    'description' => 'BLIK',
                    'currency' => 'PLN',
                    'transactionLimits' => [
                        'maxTransaction' => ['type' => 'number', 'value' => 5000000],
                        'minTransaction' => ['type' => 'number', 'value' => 1],
                    ],
                    'paymentMethodImage' => [['png' => 'https://data.imoje.pl/img/pay/blik.png']],
                    'paymentMethodCodeImage' => [['png' => 'https://data.imoje.pl/img/pay/blik.png']],
                ],
            ],
        ];
    }
}
