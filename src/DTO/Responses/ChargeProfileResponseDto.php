<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\Factories\Responses\ChargeProfileResponseDtoFactory;

/**
 * @property-read TransactionDto $transaction
 */
class ChargeProfileResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'transaction' => TransactionDto::class,
    ];

    protected static function newFactory(): ChargeProfileResponseDtoFactory
    {
        return ChargeProfileResponseDtoFactory::new();
    }
}
