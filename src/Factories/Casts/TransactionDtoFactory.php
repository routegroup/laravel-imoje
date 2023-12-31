<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
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
            'type' => TransactionType::SALE,
            'status' => TransactionStatus::SETTLED,
            'source' => TransactionSource::API,
            'created' => $now->timestamp,
            'modified' => $now->timestamp,
            'serviceId' => config('services.imoje.service_id'),
            'amount' => $this->faker->numberBetween(1, 1000) * 100,
            'currency' => Currency::PLN,
            'orderId' => $this->faker->unique()->uuid,
            'paymentMethod' => PaymentMethod::PAY_BY_LINK,
            'paymentMethodCode' => PaymentMethodCode::IPKO,
        ];
    }

    public function asApiPending(): static
    {
        return $this->state([
            'type' => TransactionType::SALE,
            'status' => TransactionStatus::PENDING,
            'source' => TransactionSource::API,
            'notificationUrl' => 'https://imoje.requestcatcher.com/',
        ]);
    }

    public function asApiRefund(): static
    {
        return $this->state([
            'type' => TransactionType::REFUND,
            'status' => TransactionStatus::SETTLED,
            'source' => TransactionSource::API,
        ]);
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

    public function withPaymentProfile(): static
    {
        return $this->state([
            'paymentProfile' => PaymentProfileDto::factory(),
        ]);
    }
}
