<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\Factories\Responses\RefundResponseDtoFactory;

/**
 * @property-read TransactionDto $transaction
 *
 * @method static RefundResponseDtoFactory factory($count = null, $state = [])
 */
class RefundResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'transaction' => TransactionDto::class,
    ];

    protected static function newFactory(): RefundResponseDtoFactory
    {
        return RefundResponseDtoFactory::new();
    }
}
