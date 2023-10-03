<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Notifications;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;
use Routegroup\Imoje\Payment\DTO\Notifications\RefundNotificationDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class RefundNotificationDtoFactory extends Factory
{
    protected $model = RefundNotificationDto::class;

    public function definition(): array
    {
        return [
            'transaction' => TransactionDto::factory(),
            'originalTransactionId' => $this->faker->unique()->uuid,
            'payment' => TransactionPaymentDto::factory(),
        ];
    }
}
