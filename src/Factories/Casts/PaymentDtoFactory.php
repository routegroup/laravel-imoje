<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Casts;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

class PaymentDtoFactory extends Factory
{
    protected $model = PaymentDto::class;

    public function definition(): array
    {
        $now = now();

        return [
            'id' => $this->faker->unique()->uuid,
            'amount' => $this->faker->numberBetween(1, 1000) * 100,
            'status' => TransactionStatus::SETTLED,
            'created' => $now->timestamp,
            'orderId' => $this->faker->unique()->uuid,
            'currency' => Currency::PLN,
            'modified' => $now->timestamp,
            'serviceId' => config('services.imoje.service_key'),
        ];
    }
}
