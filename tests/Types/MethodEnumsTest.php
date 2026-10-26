<?php

namespace Routegroup\Imoje\Payment\Tests\Types;

use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\PaymentMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

/** Verbatim from the gateway's own enum errors, October 2026. */
const GATEWAY_METHODS = [
    'pbl', 'card', 'ing', 'blik', 'twisto', 'wt',
    'imoje_paylater', 'ideal', 'pis_proxy', 'lease', 'imoje_installments', 'wallet',
];

const GATEWAY_CODES = [
    'alior', 'alior_pis_proxy', 'applepay', 'blik', 'blik_oneclick', 'blik_paylater',
    'blik_recurring', 'blik_test', 'bnpparibas', 'bnpparibas_pis_proxy', 'bos', 'brebank',
    'bs', 'bspb', 'bspw', 'bzwbk', 'c2p', 'cb', 'citi', 'creditagricole', 'cs',
    'deutschebank', 'ecom3ds', 'envelo', 'eurobank', 'getin', 'gpay', 'ideabank', 'ideal',
    'imoje_twisto', 'inbank', 'inbank_0', 'ing', 'inteligo', 'inteligo_pis_proxy', 'ipko',
    'ipko_pis_proxy', 'kb', 'lease_now', 'mbank_pis_proxy', 'millennium', 'mp', 'mtransfer',
    'neobank', 'nest', 'noble', 'oneclick', 'orange', 'paypal', 'paypo', 'pbs', 'pekao24',
    'pekao_pis_proxy', 'pf', 'pg', 'plusbank', 'poczta', 'pocztowy', 'posta', 'pragma_go',
    'raiffeisenpolbank', 'recurring', 'rf', 'sporo', 'tatra', 'tmobile', 'toyota', 'twisto',
    'uc', 'viamo', 'visa_mobile', 'vub', 'vwbank', 'wt', 'wt_split',
];

/** @return list<string> */
function values(string $enum): array
{
    $values = array_column($enum::cases(), 'value');
    sort($values);

    return $values;
}

function sorted(array $values): array
{
    sort($values);

    return $values;
}

it('offers every method the gateway accepts', function (): void {
    expect(values(PaymentMethod::class))->toContain(...GATEWAY_METHODS);
});

it('offers every channel the gateway accepts', function (): void {
    expect(values(PaymentMethodCode::class))->toContain(...GATEWAY_CODES);
});

/** VISA_MOBILE and IMOJE_INSTALLMENTS are gone in 2.0: each was refused on the field its name promised. */
it('carries nothing the gateway refuses', function (): void {
    expect(values(PaymentMethod::class))->toBe(sorted(GATEWAY_METHODS))
        ->and(values(PaymentMethodCode::class))->toBe(sorted(GATEWAY_CODES));
});

/**
 * The audit read the published schemas, where paymentMethod carries ing without
 * lease and visibleMethod the reverse, and filed splitting them. The gateway
 * answers both fields with one identical list, so one enum is right.
 */
it('needs one method enum, because the gateway uses one list for both fields', function (): void {
    expect(GATEWAY_METHODS)->toHaveCount(12);
});

it('takes the method enum in a visible-method list', function (): void {
    $payload = (new PaymentDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'o-1',
        'visibleMethod' => [PaymentMethod::BLIK, PaymentMethod::LEASE, 'wt'],
    ]))->toArray();

    expect($payload['visibleMethod'])->toBe(['blik', 'lease', 'wt']);
});

it('takes the channel enum as a preselected code', function (): void {
    $payload = (new PaymentDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'o-1',
        'preselectMethodCode' => PaymentMethodCode::BLIK,
    ]))->toArray();

    expect($payload['preselectMethodCode'])->toBe('blik');
});

it('takes the method enum on a transaction too', function (): void {
    $payload = (new TransactionDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'o-1',
        'paymentMethod' => PaymentMethod::BLIK,
        'paymentMethodCode' => PaymentMethodCode::BLIK,
        'successReturnUrl' => 'https://a.pl/ok',
        'failureReturnUrl' => 'https://a.pl/no',
        'customer' => ['firstName' => 'Jan', 'lastName' => 'Kowalski', 'email' => 'jan@example.com'],
        'visibleMethod' => [PaymentMethod::BLIK],
    ]))->toArray();

    expect($payload['visibleMethod'])->toBe(['blik']);
});
