<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\PayerCardDataDtoFactory;

/**
 * @property-read string|null $brand
 * @property-read string|null $bin
 * @property-read string|null $last4
 * @property-read string|null $type
 * @property-read string|null $countryCodeAlpha2
 * @property-read string|null $countryCodeAlpha3
 * @property-read string|null $countryName
 * @property-read string|null $firstTransactionId
 *
 * @method static PayerCardDataDtoFactory factory($count = null, $state = [])
 */
class PayerCardDataDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected static function newFactory(): PayerCardDataDtoFactory
    {
        return PayerCardDataDtoFactory::new();
    }
}
