<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Request;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Lib\Config;
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
        $config = app(Config::class);

        $attributes = array_merge_recursive([
            'serviceId' => $config->serviceId,
        ], $attributes);

        return parent::make($attributes);
    }
}
