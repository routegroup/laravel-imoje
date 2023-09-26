<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;
use Routegroup\Imoje\Payment\Types\TransactionSource;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

class PaymentProfileDtoFactory extends Factory
{
    protected $model = PaymentProfileDto::class;

    public function definition(): array
    {
        $now = now();

        return [
            'id' => $this->faker->unique()->uuid,
            'type' => TransactionType::REFUND->value,
            'status' => TransactionStatus::SETTLED->value,
            'source' => TransactionSource::API->value,
            'created' => $now->timestamp,
            'modified' => $now->timestamp,
            'serviceId' => config('services.imoje.service_key'),
            'amount' => $this->faker->numberBetween(1, 100) * 100,
            'currency' => Currency::PLN->value,
            'orderId' => $this->faker->unique()->uuid,
            'paymentMethod' => PaymentMethod::CARD->value,
            'paymentMethodCode' => PaymentMethodCode::ONECLICK->value,
        ];
    }
}
