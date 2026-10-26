<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\TransactionDto as ApiTransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto as PaywallTransactionDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidFieldException;

function apiTransaction(array $overrides = []): ApiTransactionDto
{
    return new ApiTransactionDto(array_replace_recursive([
        'amount' => 12300,
        'currency' => 'PLN',
        'orderId' => 'order-1',
        'paymentMethod' => 'blik',
        'paymentMethodCode' => 'blik',
        'successReturnUrl' => 'https://a.pl/ok',
        'failureReturnUrl' => 'https://a.pl/no',
        'customer' => ['firstName' => 'Jan', 'lastName' => 'Kowalski', 'email' => 'jan@example.com'],
    ], $overrides));
}

function paywallTransaction(array $overrides = []): PaywallTransactionDto
{
    return new PaywallTransactionDto(array_replace([
        'amount' => 12300,
        'currency' => 'PLN',
        'orderId' => 'order-1',
        'customerFirstName' => 'Jan',
        'customerLastName' => 'Kowalski',
    ], $overrides));
}

it('refuses an order id longer than the gateway takes', function (): void {
    expect(fn () => apiTransaction(['orderId' => str_repeat('a', 101)]))
        ->toThrow(InvalidFieldException::class, 'at most 100 characters');
});

it('takes an order id exactly at the limit', function (): void {
    expect(apiTransaction(['orderId' => str_repeat('a', 100)])->orderId)->toHaveLength(100);
});

it('refuses a customer id longer than the gateway takes', function (): void {
    expect(fn () => new CustomerDto(['cid' => str_repeat('a', 37)]))
        ->toThrow(InvalidFieldException::class, 'at most 36 characters');
});

it('refuses a phone longer than the gateway takes', function (): void {
    expect(fn () => new CustomerDto(['phone' => str_repeat('1', 21)]))
        ->toThrow(InvalidFieldException::class, 'at most 20 characters');
});

/** The documented sets are ASCII-only on their face; the gateway allows these ranges explicitly. */
it('takes the alphabets the gateway allows', function (string $field, string $value): void {
    expect((new CustomerDto([$field => $value]))->{$field})->toBe($value);
})->with([
    'polish diacritics' => ['firstName', 'Łukasz Żółć'],
    'cyrillic' => ['firstName', 'Іван Петренко'],
    'polish surname' => ['lastName', 'Ćwiąkała-Śmiły'],
    'apostrophe' => ['lastName', "O'Brien"],
]);

it('refuses a character the gateway refuses', function (string $field, string $value): void {
    expect(fn () => new CustomerDto([$field => $value]))
        ->toThrow(InvalidFieldException::class, 'carries a character the gateway refuses');
})->with([
    'asterisk' => ['firstName', 'Jan*'],
    'brackets in a phone' => ['phone', '(48) 515515515'],
    'semicolon' => ['lastName', 'Kowalski;'],
]);

it('takes a phone written the way people write one', function (): void {
    expect((new CustomerDto(['phone' => '+48 515 515 515']))->phone)->toBe('+48 515 515 515');
});

it('refuses something that is not an email', function (): void {
    expect(fn () => new CustomerDto(['email' => 'nie-email']))
        ->toThrow(InvalidFieldException::class, 'must be an email address');
});

/**
 * The published schema allows letters, digits and a hyphen in cid. The gateway
 * takes an underscore too, and these rules follow the gateway — enforcing the
 * document would refuse a call that works.
 */
it('takes a customer id the specification would refuse', function (): void {
    expect((new CustomerDto(['cid' => 'cid_1']))->cid)->toBe('cid_1');
});

/** The point of #108: one payload, the same answer through either channel. */
it('answers the same through both channels', function (string $field, mixed $value): void {
    expect(fn () => apiTransaction(['customer' => [$field => $value]]))
        ->toThrow(InvalidFieldException::class);

    $paywallField = 'customer'.ucfirst($field);

    expect(fn () => paywallTransaction([$paywallField => $value]))
        ->toThrow(InvalidFieldException::class);
})->with([
    'name' => ['firstName', 'Jan*'],
    'phone' => ['phone', str_repeat('1', 21)],
]);

it('refuses a long order id through the paywall too', function (): void {
    expect(fn () => paywallTransaction(['orderId' => str_repeat('a', 101)]))
        ->toThrow(InvalidFieldException::class, 'at most 100 characters');
});

it('leaves an absent field alone', function (): void {
    expect((new CustomerDto(['firstName' => 'Jan']))->toArray())->not->toHaveKey('phone');
});
