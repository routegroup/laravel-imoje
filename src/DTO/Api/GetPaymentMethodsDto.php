<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Api\GetPaymentMethodsDtoFactory;
use Routegroup\Imoje\Payment\Types\Currency;

/**
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string|null $device
 * @property-read string|null $locale
 *
 * @method static GetPaymentMethodsDtoFactory factory($count = null, $state = [])
 */
class GetPaymentMethodsDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
    ];

    /**
     * @param array{
     *     // Required
     *     amount?: int,
     *     currency?: Currency|string,
     *     // Optional
     *     device?: string,
     *     locale?: string,
     * } $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function newFactory(): GetPaymentMethodsDtoFactory
    {
        return GetPaymentMethodsDtoFactory::new();
    }
}
