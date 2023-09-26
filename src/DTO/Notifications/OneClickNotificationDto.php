<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\Factories\Notifications\OneClickNotificationDtoFactory;

/**
 * @property-read TransactionDto $transaction
 * @property-read PaymentDto $payment
 */
class OneClickNotificationDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'transaction' => TransactionDto::class,
        'payment' => PaymentDto::class,
    ];

    protected static function newFactory(): OneClickNotificationDtoFactory
    {
        return OneClickNotificationDtoFactory::new();
    }
}
