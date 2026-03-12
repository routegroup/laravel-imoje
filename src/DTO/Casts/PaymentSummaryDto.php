<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

/**
 * @property-read string $id
 * @property-read TransactionStatus $status
 */
class PaymentSummaryDto extends BaseDto
{
    protected array $casts = [
        'id' => 'string',
        'status' => TransactionStatus::class,
    ];
}
