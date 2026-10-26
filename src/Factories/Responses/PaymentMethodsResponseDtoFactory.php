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
        // Mirrors a real sandbox response: the documented list of methods
        // wrapped in a {timestamp, data} envelope. The three-character keys
        // are not a typo — the gateway injects random ones alongside the
        // documented fields, and they differ between calls.
        return [
            'timestamp' => 1792953644448,
            'data' => [
                [
                    'label' => 'Płatność BLIK',
                    'isOnline' => true,
                    'image' => [
                        ['png' => 'https://cdn.pay.ing.pl/img/pay/blik.png', 'dzP' => 'NNp'],
                    ],
                    'channels' => [
                        [
                            'paymentMethod' => 'blik',
                            'paymentMethodCode' => 'blik',
                            'label' => 'Płatność BLIK',
                            'priority' => 1,
                            'isOnline' => true,
                            'image' => [
                                ['png' => 'https://cdn.pay.ing.pl/img/pay/blik.png'],
                            ],
                        ],
                    ],
                    'priority' => 1,
                    '24M' => 'yTN',
                ],
                [
                    'label' => 'Przelew tradycyjny',
                    'isOnline' => true,
                    'image' => [
                        ['png' => 'https://cdn.pay.ing.pl/img/pay/wt.png'],
                    ],
                    'channels' => [
                        [
                            'paymentMethod' => 'wt',
                            'paymentMethodCode' => 'wt',
                            'label' => 'Przelew tradycyjny',
                            'priority' => 1,
                            'isOnline' => true,
                            'image' => [['png' => 'https://cdn.pay.ing.pl/img/pay/wt.png']],
                        ],
                        [
                            'paymentMethod' => 'wt',
                            'paymentMethodCode' => 'wt_split',
                            'label' => 'Split Payment',
                            'priority' => 2,
                            'isOnline' => true,
                            'image' => [['png' => 'https://cdn.pay.ing.pl/img/pay/wt_split.png']],
                        ],
                    ],
                    'priority' => 2,
                ],
            ],
        ];
    }
}
