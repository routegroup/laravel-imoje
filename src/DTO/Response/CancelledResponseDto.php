<?php

namespace Routegroup\Imoje\Payment\DTO\Response;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Response\Api\PaymentResponseDto;

/**
 * @property-read PaymentResponseDto $payment
 */
class CancelledResponseDto extends BaseDto
{
    /**
     * {@inheritdoc}
     */
    protected array $casts = [
        'payment' => PaymentResponseDto::class,
    ];
}
