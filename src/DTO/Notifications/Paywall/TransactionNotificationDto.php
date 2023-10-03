<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications\Paywall;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\Factories\Notifications\Paywall\TransactionNotificationDtoFactory;

/**
 * @property-read TransactionDto $transaction
 * @property-read PaymentDto $payment
 */
class TransactionNotificationDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'transaction' => TransactionDto::class,
        'payment' => PaymentDto::class,
    ];

    protected static function newFactory(): TransactionNotificationDtoFactory
    {
        return TransactionNotificationDtoFactory::new();
    }
}
