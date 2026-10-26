<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Types\Currency;

it('keeps an empty title in the payload, which the gateway accepts', function (): void {
    $payload = (new PaymentDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'order-1',
        'title' => '',
    ]))->toArray();

    expect($payload)->toHaveKey('title')
        ->and($payload['title'])->toBe('');
});

it('keeps a falsy but meaningful value', function (string $key, mixed $value): void {
    $payload = (new PaymentDto(['amount' => 1000, $key => $value]))->toArray();

    expect($payload[$key])->toBe($value);
})->with([
    "orderId '0'" => ['orderId', '0'],
    "title '0'" => ['title', '0'],
]);

it('still drops a null, which the gateway rejects for a typed field', function (): void {
    $payload = (new PaymentDto([
        'amount' => 1000,
        'orderId' => 'order-1',
        'title' => null,
    ]))->toArray();

    expect($payload)->not->toHaveKey('title');
});

it('keeps an empty string inside a nested cast', function (): void {
    expect((new CustomerDto(['firstName' => 'Jan', 'lastName' => '']))->toArray())
        ->toHaveKey('lastName')
        ->and((new CustomerDto(['lastName' => '']))->lastName)->toBe('');
});

it('tells an absent flag from a false one', function (): void {
    expect((new PaymentProfileDto([]))->isActive)->toBeNull()
        ->and((new PaymentProfileDto(['isActive' => false]))->isActive)->toBeFalse()
        ->and((new PaymentProfileDto(['isActive' => true]))->isActive)->toBeTrue();
});

it('keeps a zero amount coming back from the gateway', function (): void {
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetTransactionUrl('tx-1') => Http::response([
            'transaction' => ['id' => 'tx-1', 'amount' => 1000, 'paidAmount' => 0],
        ]),
    ]);

    expect($api->getTransaction('tx-1')->transaction->paidAmount)->toBe(0);
});

it('keeps an empty description coming back from the gateway', function (): void {
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetTransactionUrl('tx-1') => Http::response([
            'transaction' => ['id' => 'tx-1', 'statusCodeDescription' => ''],
        ]),
    ]);

    expect($api->getTransaction('tx-1')->transaction->toArray())
        ->toHaveKey('statusCodeDescription');
});
