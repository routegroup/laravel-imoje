<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JetBrains\PhpStorm\ArrayShape;
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

    public function __construct(
        #[ArrayShape([
            // Required
            'amount' => 'int',
            'currency' => Currency::class,
            // Optional
            'device' => 'string',
            'locale' => 'string',
        ])] array $attributes = []
    ) {
        parent::__construct($attributes);
    }

    protected static function newFactory(): GetPaymentMethodsDtoFactory
    {
        return GetPaymentMethodsDtoFactory::new();
    }
}
