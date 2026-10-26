<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartItemDto;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidCartException;
use Routegroup\Imoje\Payment\Types\CartItemState;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\LeaseTaxStake;

/** The example from the ING Lease Now chapter, field for field. */
function chapterCart(array $overrides = []): array
{
    return ['items' => [array_replace([
        'id' => 1,
        'categoryId' => 'category123',
        'name' => 'Product name',
        'amount' => 500000,
        'tax' => 115000,
        'taxStake' => 23,
        'quantity' => 1,
        'unit' => 'szt',
        'url' => 'https://product.url',
        'state' => 'NEW',
        'discount' => ['amount' => 100, 'tax' => 23],
    ], $overrides)]];
}

function decode(string $encoded): array
{
    return json_decode((string) gzdecode((string) base64_decode($encoded)), true);
}

it('encodes the chapter example back to the chapter example', function (): void {
    $cart = new CartDto(chapterCart());

    expect(decode($cart->encoded()))->toBe(['items' => [[
        'id' => 1,
        'categoryId' => 'category123',
        'name' => 'Product name',
        'amount' => 500000,
        'tax' => 115000,
        'taxStake' => 23,
        'quantity' => 1,
        'unit' => 'szt',
        'url' => 'https://product.url',
        'state' => 'NEW',
        'discount' => ['amount' => 100, 'tax' => 23],
    ]]]);
});

/** The gateway refuses '23' and TAX_23, and takes only the bare number. */
it('sends a percentage as a number', function (): void {
    $payload = (new CartDto(chapterCart()))->toArray();

    expect($payload['items'][0]['taxStake'])->toBe(23);
});

it('accepts every spelling of a rate and settles on the gateway’s', function (int|string $given): void {
    $item = new CartItemDto(chapterCart(['taxStake' => $given])['items'][0]);

    expect($item->taxStake)->toBe(LeaseTaxStake::RATE_23)
        ->and($item->toArray()['taxStake'])->toBe(23);
})->with([[23], ['23'], ['TAX_23']]);

it('keeps a non-numeric rate a string', function (): void {
    $item = new CartItemDto(chapterCart(['taxStake' => 'TAX_EXEMPT'])['items'][0]);

    expect($item->toArray()['taxStake'])->toBe('TAX_EXEMPT');
});

/** The gateway refuses TAX_0 but takes 0, so the invoice spelling is translated, not passed on. */
it('translates the invoice spelling of a zero rate', function (): void {
    $item = new CartItemDto(chapterCart(['taxStake' => 'TAX_0'])['items'][0]);

    expect($item->taxStake)->toBe(LeaseTaxStake::RATE_0)
        ->and($item->toArray()['taxStake'])->toBe(0);
});

it('refuses a rate that is in neither vocabulary', function (int|string $given): void {
    expect(fn () => new CartItemDto(chapterCart(['taxStake' => $given])['items'][0]))
        ->toThrow(InvalidCartException::class, 'items.taxStake');
})->with([[999], ['TAX_99'], ['nonsense']]);

it('types the product state', function (): void {
    $item = new CartItemDto(chapterCart(['state' => 'USED'])['items'][0]);

    expect($item->state)->toBe(CartItemState::USED);
});

it('demands every field the gateway demands', function (string $field): void {
    $item = chapterCart()['items'][0];
    unset($item[$field]);

    expect(fn () => new CartItemDto($item))->toThrow(InvalidCartException::class, "items.$field");
})->with(['id', 'name', 'amount', 'tax', 'taxStake', 'quantity', 'url', 'categoryId']);

it('demands at least one item', function (): void {
    expect(fn () => new CartDto(['items' => []]))->toThrow(InvalidCartException::class, 'cart.items');
});

it('demands a quantity of at least one', function (): void {
    expect(fn () => new CartItemDto(chapterCart(['quantity' => 0])['items'][0]))
        ->toThrow(InvalidCartException::class, 'items.quantity');
});

it('demands a real url', function (): void {
    expect(fn () => new CartItemDto(chapterCart(['url' => 'nie jest url'])['items'][0]))
        ->toThrow(InvalidCartException::class, 'items.url');
});

/** The gateway takes the discount pair or nothing. */
it('demands both halves of a discount', function (string $half): void {
    expect(fn () => new CartItemDto(chapterCart(['discount' => [$half => 100]])['items'][0]))
        ->toThrow(InvalidCartException::class, 'discount');
})->with(['amount', 'tax']);

it('keeps a fractional tax fractional', function (): void {
    $item = new CartItemDto(chapterCart(['tax' => 115000.5])['items'][0]);

    expect($item->toArray()['tax'])->toBe(115000.5);
});

it('types the cart on a payment link', function (): void {
    $payment = new PaymentDto([
        'amount' => 615000,
        'currency' => Currency::PLN,
        'orderId' => 'lease-1',
        'cart' => chapterCart(),
    ]);

    expect($payment->cart)->toBeInstanceOf(CartDto::class)
        ->and($payment->toArray()['cart']['items'][0]['taxStake'])->toBe(23);
});

it('encodes the cart on the paywall', function (): void {
    $dto = new TransactionDto([
        'amount' => 615000,
        'currency' => 'PLN',
        'orderId' => 'lease-1',
        'customerFirstName' => 'Jan',
        'customerLastName' => 'Kowalski',
        'cart' => chapterCart(),
    ]);

    expect(decode($dto->toArray()['cart'])['items'][0]['taxStake'])->toBe(23);
});
