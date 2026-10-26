<?php

namespace Routegroup\Imoje\Payment\Tests\Types;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

/** Verbatim from the gateway's own enum error, October 2026. */
const GATEWAY_CURRENCIES = ['PLN', 'EUR', 'USD', 'CHF', 'CZK', 'GBP', 'RON', 'HUF', 'SEK', 'BGN'];

/** Chapter 04, which is the only list of statuses either document carries. */
const DOCUMENTED_STATUSES = ['new', 'pending', 'settled', 'authorized', 'rejected', 'cancelled', 'error'];

function enumValues(string $enum): array
{
    $values = array_column($enum::cases(), 'value');
    sort($values);

    return $values;
}

function sortedList(array $values): array
{
    sort($values);

    return $values;
}

it('offers every currency the gateway accepts', function (): void {
    expect(enumValues(Currency::class))->toContain(...GATEWAY_CURRENCIES);
});

/** Probed: the gateway answers 422 for both, where PLN and EUR answer 200. */
it('carries only the two refused currencies beyond that', function (): void {
    expect(array_values(array_diff(enumValues(Currency::class), sortedList(GATEWAY_CURRENCIES))))
        ->toBe([Currency::HRK->value, Currency::UAH->value]);
});

it('offers every status the chapter documents', function (): void {
    expect(enumValues(TransactionStatus::class))->toContain(...DOCUMENTED_STATUSES);
});

it('carries only the two undocumented statuses beyond that', function (): void {
    expect(array_values(array_diff(enumValues(TransactionStatus::class), sortedList(DOCUMENTED_STATUSES))))
        ->toBe([TransactionStatus::REFUND->value, TransactionStatus::SUBMITTED_FOR_SETTLEMENT->value]);
});

/** The specification makes refund a transaction type, which is where it belongs. */
it('keeps refund a transaction type as well', function (): void {
    expect(TransactionType::tryFrom('refund'))->toBe(TransactionType::REFUND)
        ->and(TransactionType::tryFrom('sale'))->toBe(TransactionType::SALE);
});

/**
 * This is why the two undocumented statuses are kept rather than removed: a
 * response hydrates with tryFrom, so a case that is gone comes back as null
 * instead of an error, and the caller never learns what the gateway said.
 */
it('hydrates a status it does not know to null rather than failing', function (): void {
    $api = app(Api::class);
    Http::fake([
        $api->url->createGetTransactionUrl('tx-1') => Http::response([
            'transaction' => ['id' => 'tx-1', 'status' => 'a_status_from_the_future'],
        ]),
    ]);

    expect($api->getTransaction('tx-1')->transaction->status)->toBeNull();
});
