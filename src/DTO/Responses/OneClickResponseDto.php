<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;

/**
 * @property-read TransactionDto $transaction
 * @property-read PaymentDto $payment
 */
class OneClickResponseDto extends ResponseDto
{
    protected array $casts = [
        'transaction' => TransactionDto::class,
        'payment' => PaymentDto::class,
    ];
}
