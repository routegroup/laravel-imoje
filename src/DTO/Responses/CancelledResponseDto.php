<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;

/**
 * @property-read PaymentDto $payment
 */
class CancelledResponseDto extends ResponseDto
{
    protected array $casts = [
        'payment' => PaymentDto::class,
    ];
}
