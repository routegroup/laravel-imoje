<?php

declare(strict_types=1);

namespace Routegroup\Imoje\Payment\DTO\Responses;

use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;

/**
 * @property-read string $id
 * @property-read string $url
 * @property-read string $serviceId
 * @property-read string $orderId
 * @property-read string|null $title
 * @property-read string|null $simp
 * @property-read int $amount
 * @property-read Currency $currency
 * @property-read TransactionStatus $status
 * @property-read bool $isActive
 * @property-read int|null $validTo
 * @property-read int $created
 * @property-read int $modified
 * @property-read bool $isGenerated
 * @property-read bool $isUsed
 * @property-read int|null $usedAt
 * @property-read bool $isConfirmVisited
 * @property-read int|null $confirmVisitedAt
 * @property-read string|null $returnUrl
 * @property-read string|null $failureReturnUrl
 * @property-read string|null $successReturnUrl
 * @property-read string|null $notificationUrl
 * @property-read CustomerDto $customer
 * @property-read array $transactions
 */
class GetPaymentResponseDto extends ResponseDto
{
    protected array $casts = [
        'id' => 'string',
        'url' => 'string',
        'serviceId' => 'string',
        'orderId' => 'string',
        'amount' => 'int',
        'currency' => Currency::class,
        'status' => TransactionStatus::class,
        'isActive' => 'bool',
        'created' => 'int',
        'modified' => 'int',
        'isGenerated' => 'bool',
        'isUsed' => 'bool',
        'isConfirmVisited' => 'bool',
        'customer' => CustomerDto::class,
    ];
}
