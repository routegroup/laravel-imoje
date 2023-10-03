<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications\Paywall;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;
use Routegroup\Imoje\Payment\Factories\Notifications\Paywall\OneClickNotificationDtoFactory;

/**
 * @property-read TransactionDto $transaction
 * @property-read TransactionPaymentDto $payment
 */
class OneClickNotificationDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'transaction' => TransactionDto::class,
        'payment' => TransactionPaymentDto::class,
    ];

    protected static function newFactory(): OneClickNotificationDtoFactory
    {
        return OneClickNotificationDtoFactory::new();
    }
}
