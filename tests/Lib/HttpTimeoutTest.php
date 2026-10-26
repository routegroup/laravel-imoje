<?php

namespace Routegroup\Imoje\Payment\Tests\Lib;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\Exceptions\ApiErrorException;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Lib\Config;

/** @return array<string, mixed> the Guzzle options the client actually went out with */
function optionsOf(callable $call): array
{
    $captured = [];

    Http::fake(function ($request, $options) use (&$captured) {
        $captured = $options;

        return Http::response([]);
    });

    $call(app(Api::class));

    return $captured;
}

it('sends the timeouts Laravel would have applied anyway', function (): void {
    $options = optionsOf(fn (Api $api) => $api->getServices());

    expect($options['timeout'])->toBe(30)
        ->and($options['connect_timeout'])->toBe(10);
});

it('sends the timeouts the integration configured', function (): void {
    config()->set('services.imoje.timeout', 4);
    config()->set('services.imoje.connect_timeout', 2);

    $options = optionsOf(fn (Api $api) => $api->getServices());

    expect($options['timeout'])->toBe(4)
        ->and($options['connect_timeout'])->toBe(2);
});

it('takes a sub-second timeout', function (): void {
    config()->set('services.imoje.timeout', 1.5);

    expect(app(Config::class)->timeout)->toBe(1.5)
        ->and(optionsOf(fn (Api $api) => $api->getServices())['timeout'])->toBe(1.5);
});

/**
 * Chapter 05 answers 503 and 504 with one GET five minutes later, not another
 * attempt, and repeating a POST the gateway may have accepted risks a second
 * transaction. If this ever fails, the reasoning changed — not just the code.
 */
it('does not try again when the gateway is unavailable', function (int $status): void {
    $api = app(Api::class);
    Http::fake([$api->url->createServicesUrl() => Http::response([], $status)]);

    // A 500 throws by design; what matters here is how many attempts it took.
    try {
        $api->getServices();
    } catch (ApiErrorException) {
    }

    Http::assertSentCount(1);
})->with([500, 503, 504]);
