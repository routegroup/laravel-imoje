<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\BillingDtoFactory;

/**
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string|null $company
 * @property-read string|null $street
 * @property-read string|null $city
 * @property-read string|null $region
 * @property-read string|null $postalCode
 * @property-read string|null $countryCodeAlpha2
 *
 * @method static BillingDtoFactory factory($count = null, $state = [])
 */
class BillingDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
    ];

    protected static function newFactory(): BillingDtoFactory
    {
        return BillingDtoFactory::new();
    }
}
