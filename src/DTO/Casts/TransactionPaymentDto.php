<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\TransactionPaymentDtoFactory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

/**
 * @property-read string $id
 * @property-read string|null $title
 * @property-read int $amount
 * @property-read TransactionStatus $status
 * @property-read int $created
 * @property-read string $orderId
 * @property-read Currency $currency
 * @property-read int $modified
 * @property-read string $serviceId
 *
 * @method static TransactionPaymentDtoFactory factory($count = null, $state = [])
 */
class TransactionPaymentDto extends BaseDto
{
    use HasFactory;

    protected array $casts = [
        'id' => 'string',
        'amount' => 'int',
        'status' => TransactionStatus::class,
        'created' => 'int',
        'orderId' => 'string',
        'currency' => Currency::class,
        'modified' => 'int',
        'serviceId' => 'string',
    ];

    protected static function newFactory(): TransactionPaymentDtoFactory
    {
        return TransactionPaymentDtoFactory::new();
    }
}
