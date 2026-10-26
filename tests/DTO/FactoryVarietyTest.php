<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\PutTrustedIpsDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartDiscountDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartDto;
use Routegroup\Imoje\Payment\DTO\Casts\CartItemDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoicePositionDto;
use Routegroup\Imoje\Payment\DTO\Casts\ServiceDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\VatExemptionDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfilesResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ServiceResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TransactionResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TrustedIpsResponseDto;
use Routegroup\Imoje\Payment\Types\LeaseTaxStake;
use Routegroup\Imoje\Payment\Types\TaxStake;

/** A factory that always returns the same row exercises one point in the input space. */
it('varies what it builds', function (string $dto, string $field): void {
    $seen = array_map(
        static fn (): mixed => data_get($dto::factory()->make()->toArray(), $field),
        range(1, 20),
    );

    expect(array_unique($seen, SORT_REGULAR))->not->toHaveCount(1);
})->with([
    'put trusted ips' => [PutTrustedIpsDto::class, 'trustedIps.0'],
    'trusted ips response' => [TrustedIpsResponseDto::class, 'trustedIps.0'],
    'cart discount' => [CartDiscountDto::class, 'amount'],
    'cart item id' => [CartItemDto::class, 'id'],
    'cart item url' => [CartItemDto::class, 'url'],
    'cart item rate' => [CartItemDto::class, 'taxStake'],
    'cart' => [CartDto::class, 'items.0.name'],
    'invoice id' => [InvoiceDto::class, 'invoiceId'],
    'invoice position amount' => [InvoicePositionDto::class, 'grossAmount'],
    'invoice position rate' => [InvoicePositionDto::class, 'taxStake'],
    'vat exemption' => [VatExemptionDto::class, 'type'],
    'service data created' => [ServiceDataDto::class, 'created'],
    'service data channel' => [ServiceDataDto::class, 'paymentMethods.0.paymentMethodCode'],
]);

/** These delegate to a factory that already varies, so they inherit it rather than repeat it. */
it('inherits variety from the factory it delegates to', function (string $dto, string $field): void {
    $seen = array_map(
        static fn (): mixed => data_get($dto::factory()->make()->toArray(), $field),
        range(1, 20),
    );

    expect(array_unique($seen, SORT_REGULAR))->not->toHaveCount(1);
})->with([
    'profile' => [ProfileResponseDto::class, 'paymentProfile.id'],
    'profiles' => [ProfilesResponseDto::class, 'paymentProfiles.0.id'],
    'service' => [ServiceResponseDto::class, 'service.id'],
    'transaction' => [TransactionResponseDto::class, 'transaction.id'],
    'refund' => [RefundResponseDto::class, 'transaction.id'],
    'charge profile' => [ChargeProfileResponseDto::class, 'transaction.id'],
]);

/** Building the DTO runs its validation, so twenty rounds is twenty chances to break it. */
it('never builds a row its own DTO would reject', function (string $dto): void {
    foreach (range(1, 20) as $ignored) {
        expect($dto::factory()->make()->toArray())->toBeArray()->not->toBeEmpty();
    }
})->with([
    [PutTrustedIpsDto::class],
    [TrustedIpsResponseDto::class],
    [CartDiscountDto::class],
    [CartItemDto::class],
    [CartDto::class],
    [InvoiceDto::class],
    [InvoicePositionDto::class],
    [VatExemptionDto::class],
    [ServiceDataDto::class],
]);

/** Randomising a rate must not leave a tax that contradicts it. */
it('keeps a generated tax consistent with its rate', function (): void {
    foreach (range(1, 20) as $ignored) {
        $item = CartItemDto::factory()->make()->toArray();
        $rate = is_numeric($item['taxStake']) ? (int) $item['taxStake'] : 0;

        expect($item['tax'])->toBe((int) round($item['amount'] * $rate / 100));
    }
});

it('keeps a generated invoice position inside the gateway’s vocabulary', function (): void {
    foreach (range(1, 20) as $ignored) {
        $position = InvoicePositionDto::factory()->make();

        expect($position->taxStake)->toBeInstanceOf(TaxStake::class)
            ->and($position->quantity)->toBeGreaterThanOrEqual(1)
            ->and($position->taxAmount)->toBeLessThan($position->grossAmount + 1);
    }
});

it('keeps a generated cart item inside the gateway’s vocabulary', function (): void {
    foreach (range(1, 20) as $ignored) {
        $item = CartItemDto::factory()->make();

        expect($item->taxStake)->toBeInstanceOf(LeaseTaxStake::class)
            ->and($item->quantity)->toBeGreaterThanOrEqual(1)
            ->and(filter_var($item->url, FILTER_VALIDATE_URL))->not->toBeFalse();
    }
});

/** OTHER needs a description, so the default state stays clear of it. */
it('builds an exemption that does not need a description', function (): void {
    foreach (range(1, 20) as $ignored) {
        expect(VatExemptionDto::factory()->make()->toArray())->not->toHaveKey('text');
    }
});

it('builds the described exemption on request', function (): void {
    expect(VatExemptionDto::factory()->other()->make()->text)->toStartWith('Zwolnienie na podstawie');
});
