<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JetBrains\PhpStorm\ArrayShape;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Requests\ChargeProfileRequestDtoFactory;
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
    use HasFactory;

    protected array $casts = [
        'amount' => 'int',
        'currency' => Currency::class,
    ];

    public function __construct(
        #[ArrayShape([
            // Required
            'paymentProfileId' => 'string',
            'amount' => 'int',
            'currency' => Currency::class,
            'orderId' => 'string',
            // Required but provided by default
            'serviceId' => 'string',
            // Optional
            'title' => 'string',
        ])] array $attributes = []
    ) {
        $config = app(Config::class);

        $attributes = array_merge_recursive([
            'serviceId' => $config->serviceId,
        ], $attributes);

        parent::__construct($attributes);
    }

    protected static function newFactory(): ChargeProfileRequestDtoFactory
    {
        return ChargeProfileRequestDtoFactory::new();
    }
}
