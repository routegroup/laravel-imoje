<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\TransactionLimitsDtoFactory;

/**
 * @property-read TransactionLimitDto|null $minTransaction
 * @property-read TransactionLimitDto|null $maxTransaction
 *
 * @method static TransactionLimitsDtoFactory factory($count = null, $state = [])
 */
class TransactionLimitsDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'minTransaction' => TransactionLimitDto::class,
        'maxTransaction' => TransactionLimitDto::class,
    ];

    protected static function newFactory(): TransactionLimitsDtoFactory
    {
        return TransactionLimitsDtoFactory::new();
    }
}
