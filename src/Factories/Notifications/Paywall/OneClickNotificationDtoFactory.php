<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\Factories\Notifications\Paywall;

use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;
use Routegroup\Imoje\Payment\DTO\Notifications\Paywall\OneClickNotificationDto;
use Routegroup\Imoje\Payment\Factories\Factory;

class OneClickNotificationDtoFactory extends Factory
{
    protected $model = OneClickNotificationDto::class;

    public function definition(): array
    {
        return [
            'transaction' => TransactionDto::factory()->asOneClick(),
            'payment' => TransactionPaymentDto::factory(),
        ];
    }
}
