<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;

/**
 * @property-read TransactionDto $transaction
 * @property-read string $originalTransactionId
 * @property-read TransactionPaymentDto $payment
 */
class RefundNotificationDto extends BaseDto
{
    protected array $casts = [
        'transaction' => TransactionDto::class,
        'originalTransactionId' => 'string',
        'payment' => TransactionPaymentDto::class,
    ];
}
