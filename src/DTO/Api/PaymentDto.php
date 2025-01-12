<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\Factories\Api\PaymentDtoFactory;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Currency;

/**
 * @property-read string $serviceId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read CustomerDto $customer
 * @property-read string $title
 * @property-read string $visibleMethod
 * @property-read string $preselectMethodCode
 * @property-read string $returnUrl
 * @property-read string $successReturnUrl
 * @property-read string $failureReturnUrl
 * @property-read string $simp
 * @property-read int $validTo
 * @property-read array $cart
 *
 * @method static PaymentDtoFactory factory($count = null, $state = [])
 */
class PaymentDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
        'customer' => CustomerDto::class,
    ];

    public function __construct(
        #[ArrayShape([
            // Required
            'amount' => 'int',
            'currency' => 'string',
            'orderId' => 'string',
            'customer' => 'object',
            // Required but provided,
            'serviceId' => 'string',
            // Optional
            'title' => 'string',
            'visibleMethod' => 'string',
            'preselectMethodCode' => 'string',
            'returnUrl' => 'string',
            'successReturnUrl' => 'string',
            'failureReturnUrl' => 'string',
            'simp' => 'string',
            'validTo' => 'int',
            'cart' => 'array',
        ])] array $attributes = []
    ) {
        $config = app(Config::class);

        $attributes = array_merge_recursive([
            'serviceId' => $config->serviceId,
        ], $attributes);

        parent::__construct($attributes);
    }

    protected static function newFactory(): PaymentDtoFactory
    {
        return PaymentDtoFactory::new();
    }
}
