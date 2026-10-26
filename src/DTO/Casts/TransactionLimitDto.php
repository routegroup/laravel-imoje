<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\TransactionLimitDtoFactory;

/**
 * @property-read string|null $type
 * @property-read int|null $value In the currency's smallest unit
 *
 * @method static TransactionLimitDtoFactory factory($count = null, $state = [])
 */
class TransactionLimitDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = ['value' => 'int'];

    protected static function newFactory(): TransactionLimitDtoFactory
    {
        return TransactionLimitDtoFactory::new();
    }
}
