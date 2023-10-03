<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;
use Routegroup\Imoje\Payment\Factories\Notifications\RefundNotificationDtoFactory;

/**
 * @property-read TransactionDto $transaction
 * @property-read TransactionPaymentDto $payment
 * @property-read string $originalTransactionId
 */
class RefundNotificationDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'transaction' => TransactionDto::class,
        'payment' => TransactionPaymentDto::class,
        'originalTransactionId' => 'string',
    ];

    protected static function newFactory(): RefundNotificationDtoFactory
    {
        return RefundNotificationDtoFactory::new();
    }
}
