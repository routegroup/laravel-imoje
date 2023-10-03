<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Paywall;

use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\HashMethod;

/**
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read string $customerId
 * @property-read string $customerFirstName
 * @property-read string $customerLastName
 * @property-read string $serviceId
 * @property-read string $merchantId
 * @property-read string $signature
 * @property-read string $customerEmail
 * @property-read string $customerPhone
 * @property-read string $urlSuccess
 * @property-read string $urlFailure
 * @property-read string $urlReturn
 * @property-read string $orderDescription
 * @property-read string $visibleMethod
 * @property-read int $validTo
 */
class TransactionDto extends BaseDto
{
    protected array $casts = [
        'amount' => 'int',
    ];

    public function __construct(
        #[ArrayShape([
            // Required
            'amount' => 'int',
            'currency' => 'string',
            'orderId' => 'string',
            'customerFirstName' => 'string',
            'customerLastName' => 'string',
            // Required but provided
            'serviceId' => 'string',
            'merchantId' => 'string',
            'signature' => 'string',
            // Optional
            'customerEmail' => 'string',
            'customerPhone' => 'string',
            'urlSuccess' => 'string',
            'urlFailure' => 'string',
            'urlReturn' => 'string',
            'orderDescription' => 'string',
            'visibleMethod' => 'string', // separated by comma
            'validTo' => 'int',
        ])] $attributes = [],
        HashMethod $hashMethod = HashMethod::SHA256
    ) {
        $config = app(Config::class);
        $utils = app(Utils::class);

        $attributes = array_merge_recursive([
            'serviceId' => $config->serviceId,
            'merchantId' => $config->merchantId,
        ], $attributes);

        $attributes['signature'] = $utils->createSignature($attributes, $hashMethod);

        parent::__construct($attributes);
    }
}
