<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;

/**
 * @property-read TransactionDto $transaction
 * @property-read PaymentDto $payment
 */
class OneClickNotificationDto extends BaseDto
{
    protected array $casts = [
        'transaction' => TransactionDto::class,
        'payment' => PaymentDto::class,
    ];
}
