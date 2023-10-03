<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Notifications;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;
use Routegroup\Imoje\Payment\DTO\Notifications\RefundNotificationDto;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

class RefundNotificationDtoFactory extends Factory
{
    protected $model = RefundNotificationDto::class;

    public function definition(): array
    {
        $orderId = $this->faker->unique()->uuid;
        $transactionId = $this->faker->unique()->uuid;

        return [
            'transaction' => TransactionDto::factory()->state([
                'orderId' => $orderId,
                'type' => TransactionType::REFUND,
                'status' => TransactionStatus::SETTLED,
            ]),
            'payment' => TransactionPaymentDto::factory()->state([
                'id' => $transactionId,
                'orderId' => $orderId,
                'status' => TransactionStatus::SETTLED,
            ]),
            'originalTransactionId' => $transactionId,
        ];
    }
}
