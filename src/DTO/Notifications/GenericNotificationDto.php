<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionPaymentDto;

/**
 * @property-read TransactionDto $transaction
 * @property-read TransactionPaymentDto $payment
 * @property-read ActionDto|null $action
 */
class GenericNotificationDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'transaction' => TransactionDto::class,
        'payment' => TransactionPaymentDto::class,
        'action' => ActionDto::class,
    ];

    protected static function newFactory(): TransactionNotificationDtoFactory
    {
        return TransactionNotificationDtoFactory::new();
    }
}
