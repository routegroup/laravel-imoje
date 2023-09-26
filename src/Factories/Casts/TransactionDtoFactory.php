<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;
use Routegroup\Imoje\Payment\Types\TransactionSource;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

class TransactionDtoFactory extends Factory
{
    protected $model = TransactionDto::class;

    public function definition(): array
    {
        $now = now();

        return [
            'id' => $this->faker->unique()->uuid,
            'type' => TransactionType::REFUND,
            'status' => TransactionStatus::SETTLED,
            'source' => TransactionSource::API,
            'created' => $now->timestamp,
            'modified' => $now->timestamp,
            'serviceId' => config('services.imoje.service_key'),
            'amount' => $this->faker->numberBetween(1, 100) * 100,
            'currency' => Currency::PLN,
            'orderId' => $this->faker->unique()->uuid,
            'paymentMethod' => $this->faker->randomElement(PaymentMethod::cases()),
            'paymentMethodCode' => $this->faker->randomElement(PaymentMethodCode::cases()),
        ];
    }

    public function asOneClick(): static
    {
        return $this->state([
            'type' => TransactionType::SALE,
            'status' => TransactionStatus::SETTLED,
            'source' => TransactionSource::WEB,
            'paymentMethod' => PaymentMethod::CARD,
            'paymentMethodCode' => PaymentMethodCode::ONECLICK,
        ]);
    }
}
