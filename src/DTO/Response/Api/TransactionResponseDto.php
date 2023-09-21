<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Response\Api;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

/**
 * @property-read int $amount
 * @property-read int $created
 * @property-read int $modified
 * @property-read Currency $currency
 * @property-read string $id
 * @property-read string $notificationUrl
 * @property-read string $orderId
 * @property-read PaymentMethod $paymentMethod
 * @property-read PaymentMethodCode $paymentMethodCode
 * @property-read PaymentProfileResponseDto $paymentProfile
 * @property-read string $serviceId
 * @property-read string $source
 * @property-read TransactionStatus $status
 * @property-read string $statusCode
 * @property-read string $statusCodeDescription
 * @property-read string $title
 * @property-read string $type
 */
class TransactionResponseDto extends BaseDto
{
    /**
     * {@inheritdoc}
     */
    protected array $casts = [
        'amount' => 'int',
        'created' => 'int',
        'modified' => 'int',
        'currency' => Currency::class,
        'paymentMethod' => PaymentMethod::class,
        'paymentMethodCode' => PaymentMethodCode::class,
        'paymentProfile' => PaymentProfileResponseDto::class,
        'status' => TransactionStatus::class,
    ];
}
