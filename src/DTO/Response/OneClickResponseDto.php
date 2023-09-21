<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Response;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Response\Api\PaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Response\Api\TransactionResponseDto;

/**
 * @property-read TransactionResponseDto $transaction
 * @property-read PaymentResponseDto $payment
 */
class OneClickResponseDto extends BaseDto
{
    /**
     * {@inheritdoc}
     */
    protected array $casts = [
        'transaction' => TransactionResponseDto::class,
        'payment' => PaymentResponseDto::class,
    ];
}
