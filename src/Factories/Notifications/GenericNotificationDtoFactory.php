<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Notifications;

use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;
use Routegroup\Imoje\Payment\DTO\Notifications\GenericNotificationDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

class GenericNotificationDtoFactory extends Factory
{
    protected $model = GenericNotificationDto::class;

    public function definition(): array
    {
        $orderId = $this->faker->unique()->uuid;
        $transactionId = $this->faker->unique()->uuid;

        return [
            'transaction' => TransactionDto::factory()->state([
                'id' => $transactionId,
                'title' => $orderId,
                'orderId' => $orderId,
                'type' => TransactionType::SALE,
                'status' => TransactionStatus::SETTLED,
            ]),
            'payment' => TransactionPaymentDto::factory()->state([
                'id' => $transactionId,
                'orderId' => $orderId,
                'status' => TransactionStatus::SETTLED,
            ]),
        ];
    }

    public function asPending(): static
    {
        $orderId = $this->faker->unique()->uuid;
        $transactionId = $this->faker->unique()->uuid;

        return $this->state([
            'transaction' => TransactionDto::factory()->state([
                'id' => $transactionId,
                'title' => $orderId,
                'orderId' => $orderId,
                'type' => TransactionType::SALE,
                'status' => TransactionStatus::PENDING,
            ]),
            'payment' => TransactionPaymentDto::factory()->state([
                'id' => $transactionId,
                'orderId' => $orderId,
                'status' => TransactionStatus::PENDING,
            ]),
            'action' => ActionDto::factory(),
        ]);
    }
}
