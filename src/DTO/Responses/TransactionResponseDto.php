<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\Casts\ActionDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\Factories\Responses\TransactionResponseDtoFactory;

/**
 * @property-read TransactionDto $transaction
 * @property-read ActionDto $action
 *
 * @method static TransactionResponseDtoFactory factory($count = null, $state = [])
 */
class TransactionResponseDto extends ResponseDto
{
    use HasFactory;

    protected array $casts = [
        'transaction' => TransactionDto::class,
        'action' => ActionDto::class,
    ];

    protected static function newFactory(): TransactionResponseDtoFactory
    {
        return TransactionResponseDtoFactory::new();
    }
}
