<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Casts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Factories\Casts\TransactionDtoFactory;
use Routegroup\Imoje\Payment\Factories\Factory;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;
use Routegroup\Imoje\Payment\Types\TransactionSource;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

/**
 * @property-read string $id
 * @property-read TransactionType $type
 * @property-read TransactionStatus $status
 * @property-read TransactionSource $source
 * @property-read int $created
 * @property-read int $modified
 * @property-read string|null $notificationUrl
 * @property-read string $serviceId
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read string $title
 * @property-read string $orderId
 * @property-read PaymentMethod $paymentMethod
 * @property-read PaymentMethodCode $paymentMethodCode
 * @property-read PaymentProfileDto|null $paymentProfile
 * @property-read string|null $statusCode
 * @property-read string|null $statusCodeDescription
 *
 * @method static TransactionDtoFactory factory($count = null, $state = [])
 */
class TransactionDto extends BaseDto
{
    use HasFactory;

    protected bool $allowNull = true;

    protected array $casts = [
        'id' => 'string',
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'source' => TransactionSource::class,
        'created' => 'int',
        'modified' => 'int',
        'serviceId' => 'string',
        'amount' => 'int',
        'currency' => Currency::class,
        'orderId' => 'string',
        'paymentMethod' => PaymentMethod::class,
        'paymentMethodCode' => PaymentMethodCode::class,
        'paymentProfile' => PaymentProfileDto::class,
    ];

    protected static function newFactory(): TransactionDtoFactory
    {
        return TransactionDtoFactory::new();
    }
}
