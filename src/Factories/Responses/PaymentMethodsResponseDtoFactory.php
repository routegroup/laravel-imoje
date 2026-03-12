<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Responses;

use Routegroup\Imoje\Payment\DTO\Responses\PaymentMethodsResponseDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class PaymentMethodsResponseDtoFactory extends Factory
{
    protected $model = PaymentMethodsResponseDto::class;

    public function definition(): array
    {
        return [
            [
                'label' => 'Płatność BLIK',
                'isOnline' => true,
                'image' => [
                    ['png' => 'https://data.imoje.pl/img/pay/blik.png'],
                ],
                'channels' => [
                    [
                        'paymentMethod' => 'blik',
                        'paymentMethodCode' => 'blik',
                        'label' => 'Płatność BLIK',
                        'priority' => 1,
                        'isOnline' => true,
                        'image' => [
                            ['png' => 'https://data.imoje.pl/img/pay/blik.png'],
                        ],
                    ],
                ],
                'priority' => 1,
            ],
        ];
    }
}
