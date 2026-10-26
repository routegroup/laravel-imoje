<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\RefundDto;
use Routegroup\Imoje\Payment\DTO\Api\TransactionDto as RequestTransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use ValueError;

it('hydrates an unknown enum value from the gateway to null', function (string $field, mixed $value): void {
    $dto = new TransactionDto([$field => $value]);

    expect($dto->{$field})->toBeNull();
})->with([
    'a channel ING has not shipped yet' => ['paymentMethodCode', 'brand_new_bank'],
    'an unknown method' => ['paymentMethod', 'crypto'],
    'an unknown status' => ['status', 'disputed'],
    'an unknown currency' => ['currency', 'XYZ'],
]);

it('keeps hydrating the known values', function (): void {
    $dto = new TransactionDto(['status' => 'settled', 'currency' => 'PLN']);

    expect($dto->status)->toBe(TransactionStatus::SETTLED)
        ->and($dto->currency)->toBe(Currency::PLN);
});

it('survives a whole response carrying an unknown channel', function (): void {
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetTransactionUrl('tx-1') => Http::response([
            'transaction' => [
                'id' => 'tx-1',
                'status' => 'settled',
                'paymentMethodCode' => 'brand_new_bank',
                'currency' => 'PLN',
            ],
        ]),
    ]);

    $response = $api->getTransaction('tx-1');

    expect($response->transaction->paymentMethodCode)->toBeNull()
        ->and($response->transaction->status)->toBe(TransactionStatus::SETTLED);
});

it('still rejects an unknown enum value in a request', function (string $dto, array $attributes): void {
    expect(fn () => new $dto($attributes))->toThrow(ValueError::class);
})->with([
    'transaction currency' => [RequestTransactionDto::class, ['currency' => 'XYZ']],
    'transaction method' => [RequestTransactionDto::class, ['paymentMethod' => 'crypto']],
    'payment currency' => [PaymentDto::class, ['currency' => 'XYZ']],
    'refund type' => [RefundDto::class, ['type' => 'chargeback']],
]);

it('keeps accepting valid values in a request', function (): void {
    $payload = (new PaymentDto(['amount' => 100, 'currency' => 'PLN']))->toArray();

    expect($payload['currency'])->toBe('PLN');
});

it('is lenient inside a cast nested in a request', function (string $locale): void {
    expect((new CustomerDto(['locale' => $locale]))->locale)->toBeNull();
})->with([
    'nonsense' => ['zz'],
    // The locale ING actually sends for Ukrainian, which Lang still lacks (#66).
    'uk' => ['uk'],
]);
