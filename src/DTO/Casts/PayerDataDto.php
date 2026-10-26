<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\PayerDataDtoFactory;

/**
 * @property-read string|null $name
 * @property-read string|null $account
 *
 * @method static PayerDataDtoFactory factory($count = null, $state = [])
 */
class PayerDataDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected static function newFactory(): PayerDataDtoFactory
    {
        return PayerDataDtoFactory::new();
    }
}
