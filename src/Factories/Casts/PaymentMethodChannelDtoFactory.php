<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentMethodChannelDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

class PaymentMethodChannelDtoFactory extends Factory
{
    protected $model = PaymentMethodChannelDto::class;

    public function definition(): array
    {
        $code = $this->faker->randomElement([
            PaymentMethodCode::BLIK, PaymentMethodCode::IPKO, PaymentMethodCode::MTRANSFER,
        ]);

        return [
            'paymentMethod' => $code === PaymentMethodCode::BLIK ? 'blik' : 'pbl',
            'paymentMethodCode' => $code->value,
            'label' => ucfirst($code->value),
            'priority' => $this->faker->numberBetween(1, 20),
            'isOnline' => $this->faker->boolean(80),
            'image' => [['png' => $code->getLogo('png')]],
        ];
    }
}
