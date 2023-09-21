<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Request;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;

/**
 * @property-read string $serviceId
 * @property-read string $paymentProfileId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read string $title
 */
class ChargeProfileRequestDto extends BaseDto
{
    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
    ];

    public static function make(
        #[ArrayShape([
            // Required
            'serviceId' => 'string',
            'paymentProfileId' => 'string',
            'amount' => 'int',
            'currency' => Currency::class,
            'orderId' => 'string',
            // Optional
            'title' => 'string',
        ])] array $attributes
    ): BaseDto {
        $utils = app(Utils::class);

        $attributes = array_merge_recursive([
            'serviceId' => $utils->serviceId,
        ], $attributes);

        return parent::make($attributes);
    }
}
