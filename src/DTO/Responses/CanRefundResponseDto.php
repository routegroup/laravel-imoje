<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\Factories\Responses\CanRefundResponseDtoFactory;

/**
 * @property-read bool $refundable
 * @property-read bool $balance
 * @property-read int $fullRefund
 * @property-read array $partialRefund
 *
 * @method static CanRefundResponseDtoFactory factory($count = null, $state = [])
 */
class CanRefundResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'refundable' => 'bool',
        'balance' => 'bool',
        'fullRefund' => 'int',
    ];

    protected static function newFactory(): CanRefundResponseDtoFactory
    {
        return CanRefundResponseDtoFactory::new();
    }
}
