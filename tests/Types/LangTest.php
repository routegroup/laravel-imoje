<?php

namespace Routegroup\Imoje\Payment\Tests\Types;

use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\Types\Environment;
use Routegroup\Imoje\Payment\Types\Lang;

it('covers every locale the specification lists', function (): void {
    $spec = ['pl', 'en', 'cs', 'de', 'es', 'fr', 'it', 'lt', 'ru', 'sk', 'sl', 'uk', 'nl', 'hu', 'ro', 'bg', 'sv'];

    expect(array_diff($spec, array_column(Lang::cases(), 'value')))->toBe([]);
});

it('hydrates the locales that used to blow up', function (string $value): void {
    expect((new CustomerDto(['locale' => $value]))->locale)->toBe(Lang::from($value));
})->with([['uk'], ['bg'], ['sv']]);

/** ua was an alias for uk until 2.0 removed it; ING only ever served uk. */
it('no longer answers to the spelling ING never used', function (): void {
    expect(Lang::tryFrom('ua'))->toBeNull();
});

it('builds the ukrainian paywall url', function (): void {
    expect(Environment::SANDBOX->paywallUrl(Lang::UK))
        ->toBe('https://paywall.sandbox.pay.ing.pl/uk/payment');
});

it('sends the locale it was given', function (): void {
    expect((new CustomerDto(['locale' => Lang::UK]))->toArray()['locale'])->toBe('uk');
});

it('builds a paywall url for the newly added locales', function (Lang $lang, string $segment): void {
    expect(Environment::PRODUCTION->paywallUrl($lang))
        ->toBe("https://paywall.pay.ing.pl/$segment/payment");
})->with([
    [Lang::BG, 'bg'],
    [Lang::SV, 'sv'],
    [Lang::PL, 'pl'],
]);
