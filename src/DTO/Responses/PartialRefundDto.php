<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Routegroup\Imoje\Payment\DTO\BaseDto;

/**
 * @property-read int $maxRefundAmount
 * @property-read int $minRefundAmount
 */
class PartialRefundDto extends BaseDto
{
    protected array $casts = [
        'maxRefundAmount' => 'int',
        'minRefundAmount' => 'int',
    ];
}
