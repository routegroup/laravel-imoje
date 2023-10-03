<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Notifications\Paywall;

use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Notifications\Paywall\TransactionNotificationDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class TransactionNotificationDtoFactory extends Factory
{
    protected $model = TransactionNotificationDto::class;

    public function definition(): array
    {
        return [
            'transaction' => TransactionDto::factory(),
            'payment' => PaymentDto::factory(),
            'action' => ActionDto::factory(),
        ];
    }
}
