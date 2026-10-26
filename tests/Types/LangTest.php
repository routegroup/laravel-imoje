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

it('keeps ua as a deprecated alias', function (): void {
    expect(Lang::tryFrom('ua'))->toBe(Lang::UA)
        ->and(Lang::UA->canonical())->toBe(Lang::UK);
});

it('leaves every other locale alone', function (Lang $lang): void {
    expect($lang->canonical())->toBe($lang);
})->with(array_map(
    fn (Lang $lang): array => [$lang],
    array_values(array_filter(Lang::cases(), fn (Lang $l): bool => $l !== Lang::UA)),
));

it('sends uk on the wire when the caller used the deprecated ua', function (): void {
    $payload = (new CustomerDto(['locale' => Lang::UA]))->toArray();

    expect($payload['locale'])->toBe('uk');
});

it('builds the ukrainian paywall url for both spellings', function (Lang $lang): void {
    expect(Environment::SANDBOX->paywallUrl($lang))
        ->toBe('https://paywall.sandbox.pay.ing.pl/uk/payment');
})->with([[Lang::UK], [Lang::UA]]);

it('builds a paywall url for the newly added locales', function (Lang $lang, string $segment): void {
    expect(Environment::PRODUCTION->paywallUrl($lang))
        ->toBe("https://paywall.pay.ing.pl/$segment/payment");
})->with([
    [Lang::BG, 'bg'],
    [Lang::SV, 'sv'],
    [Lang::PL, 'pl'],
]);
