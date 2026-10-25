<?php

namespace Routegroup\Imoje\Payment\Tests\Types;

use Routegroup\Imoje\Payment\Types\Environment;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

it('serves a payment method logo from the ING Pay CDN', function (): void {
    expect(PaymentMethodCode::BLIK->getLogo())
        ->toBe('https://cdn.pay.ing.pl/img/pay/blik.png');
});

it('uses the ING Pay CDN in every environment', function (Environment $environment): void {
    expect($environment->cdnUrl())->toBe('https://cdn.pay.ing.pl');
})->with([
    [Environment::PRODUCTION],
    [Environment::SANDBOX],
]);

it('never serves a logo from the retired imoje host', function (): void {
    $logos = array_filter(array_map(
        fn (PaymentMethodCode $code): ?string => $code->getLogo(),
        PaymentMethodCode::cases(),
    ));

    expect($logos)->not->toBeEmpty();

    foreach ($logos as $logo) {
        expect($logo)->toStartWith('https://cdn.pay.ing.pl/img/pay/');
    }
});
