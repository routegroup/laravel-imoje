<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\BillingDto;
use Routegroup\Imoje\Payment\DTO\Casts\ShippingDto;
use Routegroup\Imoje\Payment\Types\ProfileType;
use ValueError;

function address(array $overrides = []): array
{
    return array_replace([
        'firstName' => 'Jan',
        'lastName' => 'Kowalski',
        'street' => 'Ulica 1',
        'city' => 'Miasto',
        'postalCode' => '12-345',
        'countryCodeAlpha2' => 'PL',
    ], $overrides);
}

function transaction(array $overrides = []): TransactionDto
{
    return new TransactionDto(array_replace([
        'amount' => 12300,
        'currency' => 'PLN',
        'orderId' => 'o-1',
        'paymentMethod' => 'blik',
        'paymentMethodCode' => 'blik',
        'successReturnUrl' => 'https://a.pl/ok',
        'failureReturnUrl' => 'https://a.pl/no',
        'customer' => ['firstName' => 'Jan', 'lastName' => 'Kowalski', 'email' => 'jan@example.com'],
    ], $overrides));
}

it('carries the fields that used to slip past the contract', function (string $field, mixed $value): void {
    expect(transaction([$field => $value])->toArray()[$field])->toBe($value);
})->with([
    'notification url' => ['notificationUrl', 'https://example.com/notify'],
    'additional description' => ['additionalDescription', 'Opis dodatkowy'],
    'blik code' => ['blikCode', '777111'],
    'client ip' => ['clientIp', '192.0.2.10'],
]);

it('types the profile kind', function (): void {
    $dto = transaction(['profileType' => 'FIRST']);

    expect($dto->profileType)->toBe(ProfileType::FIRST)
        ->and($dto->toArray()['profileType'])->toBe('FIRST');
});

/** strictEnums is on for requests, so a typo fails here rather than at the gateway. */
it('refuses a profile kind the gateway does not know', function (): void {
    expect(fn () => transaction(['profileType' => 'first']))->toThrow(ValueError::class);
});

/**
 * The shipping schema has every billing field but taxId, and sending it answers
 * "is not allowed to have the additional property" — so it is dropped.
 */
it('drops the one field a shipping address may not carry', function (): void {
    $payload = transaction(['shipping' => address(['taxId' => '5354235387'])])->toArray();

    expect($payload['shipping'])->not->toHaveKey('taxId')
        ->and($payload['shipping']['city'])->toBe('Miasto');
});

it('keeps that field on a billing address', function (): void {
    $payload = transaction(['billing' => address(['taxId' => '5354235387'])])->toArray();

    expect($payload['billing']['taxId'])->toBe('5354235387');
});

/** ShippingDto extends BillingDto, so anything typed against the old class still holds. */
it('leaves a shipping address a billing address by type', function (): void {
    $shipping = transaction(['shipping' => address()])->shipping;

    expect($shipping)->toBeInstanceOf(ShippingDto::class)
        ->and($shipping)->toBeInstanceOf(BillingDto::class);
});

it('builds a shipping address without the field from its factory', function (): void {
    expect(ShippingDto::factory()->make()->toArray())->not->toHaveKey('taxId')
        ->and(BillingDto::factory()->make()->toArray())->toHaveKey('taxId');
});

/**
 * The six fields above always reached the gateway — Fluent forwards anything —
 * so the tests for them document rather than pin. What was missing was the
 * contract static analysis reads, and this is what pins that.
 */
it('declares every field the transaction schema defines', function (): void {
    $source = file_get_contents(__DIR__.'/../../src/DTO/Api/TransactionDto.php');
    $shape = substr(
        $source,
        strpos($source, '@param array{'),
        strpos($source, '} $attributes') - strpos($source, '@param array{'),
    );

    preg_match_all('/^\s*\*\s+(\w+)\?:/m', $shape, $matches);

    expect($matches[1])->toContain(...[
        'type', 'serviceId', 'amount', 'currency', 'orderId', 'paymentMethod',
        'paymentMethodCode', 'successReturnUrl', 'failureReturnUrl', 'notificationUrl',
        'title', 'additionalDescription', 'validTo', 'blikCode', 'clientIp', 'customer',
        'billing', 'shipping', 'card', 'additionalData', 'visibleMethod', 'invoice',
        'profileType', 'installment', 'wallet',
    ]);
});
