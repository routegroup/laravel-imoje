<?php

namespace Routegroup\Imoje\Payment\DTO\Request;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Lib\Utils;

/**
 * @property-read string $serviceId
 * @property-read string $paymentProfileId
 * @property-read int $amount
 * @property-read string $currency
 * @property-read string $orderID
 * @property-read string $title
 */
class ChargeProfileRequestDto extends BaseDto
{
    public static function make(
        #[ArrayShape([
            // Required
            'serviceId' => 'string',
            'paymentProfileId' => 'string',
            'amount' => 'int',
            'currency' => 'string',
            'orderID' => 'string',
            // Optional
            'title' => 'string',
        ])] array $attributes
    ): BaseDto {
        $utils = app(Utils::class);

        $attributes = array_merge_recursive([
            'type' => 'refund',
            'serviceId' => $utils->serviceId,
        ], $attributes);

        return parent::make($attributes);
    }
}
