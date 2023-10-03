<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Paywall;

use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\HashMethod;
use Routegroup\Imoje\Payment\Types\WidgetType;

/**
 * @property-read string $serviceId
 * @property-read string $merchantId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read string $customerId
 * @property-read string $customerFirstName
 * @property-read string $customerLastName
 * @property-read string $customerEmail
 * @property-read string $signature
 * @property-read string $customerPhone
 * @property-read string $orderDescription
 * @property-read string $urlSuccess
 * @property-read string $urlFailure
 * @property-read string $urlReturn
 * @property-read string $urlCancel
 * @property-read string $widgetType
 * @property-read int $validTo
 */
class OneClickDto extends BaseDto
{
    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
        'widgetType' => WidgetType::class,
    ];

    public function __construct(
        #[ArrayShape([
            // Required
            'amount' => 'int',
            'currency' => 'string',
            'orderId' => 'string',
            'customerId' => 'string',
            'customerFirstName' => 'string',
            'customerLastName' => 'string',
            'customerEmail' => 'string',
            // Required but provided
            'serviceId' => 'string',
            'merchantId' => 'string',
            'widgetType' => 'string',
            'signature' => 'string',
            // Optional
            'customerPhone' => 'string',
            'orderDescription' => 'string',
            'urlSuccess' => 'string',
            'urlFailure' => 'string',
            'urlReturn' => 'string',
            'urlCancel' => 'string',
            'validTo' => 'int',
        ])] $attributes = [],
        HashMethod $hashMethod = HashMethod::SHA256
    ) {
        $config = app(Config::class);
        $utils = app(Utils::class);

        $attributes = array_merge_recursive([
            'serviceId' => $config->serviceId,
            'merchantId' => $config->merchantId,
            'widgetType' => WidgetType::ONECLICK,
        ], $attributes);

        $attributes['signature'] = $utils->createSignature($attributes, $hashMethod);

        parent::__construct($attributes);
    }
}
