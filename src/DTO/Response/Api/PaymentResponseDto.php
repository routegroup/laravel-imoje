<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Response\Api;

use Routegroup\Imoje\Payment\DTO\BaseDto;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

/**
 * @property-read string $id
 * @property-read string $title
 * @property-read int $amount
 * @property-read TransactionStatus $status
 * @property-read int $created
 * @property-read string $orderId
 * @property-read Currency $currency
 * @property-read int $modified
 * @property-read string $serviceId
 * @property-read string $notificationUrl
 */
class PaymentResponseDto extends BaseDto
{
    protected array $casts = [
        'amount' => 'int',
        'created' => 'int',
        'modified' => 'int',
        'currency' => Currency::class,
        'status' => TransactionStatus::class,
    ];
}
