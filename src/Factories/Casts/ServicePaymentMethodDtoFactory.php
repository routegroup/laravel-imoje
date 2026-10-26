<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\ServicePaymentMethodDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionLimitsDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

class ServicePaymentMethodDtoFactory extends Factory
{
    protected $model = ServicePaymentMethodDto::class;

    public function definition(): array
    {
        $code = $this->faker->randomElement([
            PaymentMethodCode::BLIK, PaymentMethodCode::IPKO,
            PaymentMethodCode::MTRANSFER, PaymentMethodCode::WIRE_TRANSFER,
        ]);

        return [
            'paymentMethod' => $code === PaymentMethodCode::BLIK ? 'blik' : 'pbl',
            'paymentMethodCode' => $code->value,
            'isActive' => $this->faker->boolean(90),
            'isOnline' => $this->faker->boolean(80),
            'description' => ucfirst($code->value),
            'currency' => 'PLN',
            'transactionLimits' => TransactionLimitsDto::factory()->make()->toArray(),
            'paymentMethodImage' => [['png' => $code->getLogo('png')]],
            'paymentMethodCodeImage' => [['png' => $code->getLogo('png')]],
        ];
    }
}
