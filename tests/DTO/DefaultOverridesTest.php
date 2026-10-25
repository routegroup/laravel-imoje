<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\CancelPaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\ChargeProfileDto;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\RefundDto;
use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Paywall\OneClickDto;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto as PaywallTransactionDto;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionType;

it('lets an explicit serviceId replace the configured default', function (string $dto): void {
    $payload = (new $dto(['serviceId' => 'explicit-service']))->toArray();

    expect($payload['serviceId'])->toBe('explicit-service');
})->with([
    [TransactionDto::class],
    [PaymentDto::class],
    [CancelPaymentDto::class],
    [RefundDto::class],
    [ChargeProfileDto::class],
    [PaywallTransactionDto::class],
    [OneClickDto::class],
]);

it('lets an explicit merchantId replace the configured default', function (string $dto): void {
    $payload = (new $dto(['merchantId' => 'explicit-merchant']))->toArray();

    expect($payload['merchantId'])->toBe('explicit-merchant');
})->with([
    [PaywallTransactionDto::class],
    [OneClickDto::class],
]);

it('lets an explicit transaction type replace the default', function (): void {
    $payload = (new TransactionDto(['type' => TransactionType::REFUND]))->toArray();

    expect($payload['type'])->toBe(TransactionType::REFUND->value);
});

it('lets an explicit refund type replace the default', function (): void {
    $payload = (new RefundDto(['type' => TransactionType::SALE]))->toArray();

    expect($payload['type'])->toBe(TransactionType::SALE->value);
});

it('signs a paywall transaction with the overridden serviceId, not an array', function (): void {
    $config = app(Config::class);
    $utils = app(Utils::class);

    $attributes = [
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'order-1',
        'serviceId' => 'explicit-service',
    ];

    $dto = new PaywallTransactionDto($attributes);

    $expected = $utils->createSignature([
        ...$attributes,
        'merchantId' => $config->merchantId,
    ]);

    expect($dto->signature)->toBe($expected);
});
