<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\PaymentDtoFactory;
use Routegroup\Imoje\Payment\Types\Currency;

/**
 * @property-read string $serviceId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $orderId
 * @property-read CustomerDto $customer
 * @property-read string $id
 * @property-read string $url
 * @property-read int|null $validTo
 * @property-read int $created
 * @property-read int $modified
 *
 * @method static PaymentDtoFactory factory($count = null, $state = [])
 */
class PaymentDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'serviceId' => 'string',
        'amount' => 'int',
        'currency' => Currency::class,
        'orderId' => 'string',
        'customer' => CustomerDto::class,
        'id' => 'string',
        'url' => 'string',
        'created' => 'int',
        'modified' => 'int',
    ];

    protected static function newFactory(): PaymentDtoFactory
    {
        return PaymentDtoFactory::new();
    }
}
