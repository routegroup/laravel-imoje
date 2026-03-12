<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;

/**
 * @property-read TransactionDto $transaction
 * @property-read ActionDto|null $action
 */
class GetTransactionResponseDto extends ResponseDto
{
    protected array $casts = [
        'transaction' => TransactionDto::class,
        'action' => ActionDto::class,
    ];
}
