<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Response;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Response\Api\TransactionResponseDto;

/**
 * @property-read TransactionResponseDto $transaction
 */
class RefundResponseDto extends BaseDto
{
    protected array $casts = [
        'transaction' => TransactionResponseDto::class,
    ];
}
