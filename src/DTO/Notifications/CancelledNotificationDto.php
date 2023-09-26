<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Notifications;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;

/**
 * @property-read PaymentDto $payment
 */
class CancelledNotificationDto extends BaseDto
{
    protected array $casts = [
        'payment' => PaymentDto::class,
    ];
}
