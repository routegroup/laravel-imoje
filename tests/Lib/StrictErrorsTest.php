<?php

namespace Routegroup\Imoje\Payment\Tests\Lib;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\Exceptions\ApiErrorException;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\Types\Environment;

function fakeTransaction(mixed $body, int $status): Api
{
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetTransactionUrl('tx-1') => Http::response($body, $status),
    ]);

    return $api;
}

/** The failed()/error() style, which 2.0 leaves supported but no longer defaults to. */
function lenient(): void
{
    config()->set('services.imoje.strict_errors', false);
}

function envelope(string $code = 'TRX-ERROR-120101', string $message = 'Transaction in progress, cannot refund.'): array
{
    return ['apiErrorResponse' => ['code' => $code, 'message' => $message, 'errors' => ['amount' => 'too high']]];
}

it('throws on every failing code', function (int $status): void {
    expect(fn () => fakeTransaction(envelope(), $status)->getTransaction('tx-1'))
        ->toThrow(ApiErrorException::class);
})->with([[400], [401], [403], [404], [406], [422], [500], [503]]);

it('raises by default, without anything being configured', function (): void {
    expect(app(Config::class)->strictErrors)->toBeTrue();
});

/**
 * The provider always passes a value, so its default is what an application
 * gets. This pins the constructor's own, which anyone building a Config by
 * hand gets instead — the two have to agree.
 */
it('raises by default when a Config is built by hand', function (): void {
    $config = new Config('merchant', 'service', 'key', 'api-key', Environment::SANDBOX);

    expect($config->strictErrors)->toBeTrue();
});

it('keeps throwing on 422 and 500 even for the lenient', function (int $status): void {
    lenient();

    expect(fn () => fakeTransaction(envelope(), $status)->getTransaction('tx-1'))
        ->toThrow(ApiErrorException::class);
})->with([[422], [500]]);

it('stays silent on the other codes for the lenient', function (int $status): void {
    lenient();

    $response = fakeTransaction(envelope(), $status)->getTransaction('tx-1');

    expect($response->failed())->toBeTrue();
})->with([[400], [401], [403], [404], [406], [503]]);

it('reports the real status instead of a constant 422', function (): void {
    try {
        fakeTransaction(envelope(), 503)->getTransaction('tx-1');
    } catch (ApiErrorException $e) {
        expect($e->getCode())->toBe(503)
            ->and($e->status())->toBe(503)
            ->and($e->context()['status'])->toBe(503);

        return;
    }

    $this->fail('ApiErrorException was not thrown.');
});

it('reads the enveloped body', function (): void {
    lenient();

    $error = fakeTransaction(envelope(), 404)->getTransaction('tx-1')->error();

    expect($error->code)->toBe('TRX-ERROR-120101')
        ->and($error->message)->toBe('Transaction in progress, cannot refund.')
        ->and($error->errors)->toBe(['amount' => 'too high']);
});

it('reads a bare body, the shape 400 and 401 arrive in', function (): void {
    lenient();

    $error = fakeTransaction(['message' => 'Cannot cancel payment'], 400)
        ->getTransaction('tx-1')->error();

    expect($error->message)->toBe('Cannot cancel payment')
        ->and($error->code)->toBeNull()
        ->and($error->errors)->toBe([]);
});

it('gives an array for errors even when the body carries none', function (): void {
    lenient();

    $error = fakeTransaction([], 401)->getTransaction('tx-1')->error();

    expect($error->errors)->toBe([]);
});

it('falls back to the reason phrase when a bare body carries no message', function (): void {
    expect(fn () => fakeTransaction([], 401)->getTransaction('tx-1'))
        ->toThrow(ApiErrorException::class, 'Unauthorized');
});

it('reports success and no error on a 200', function (): void {
    $response = fakeTransaction(['transaction' => ['id' => 'tx-1']], 200)->getTransaction('tx-1');

    expect($response->failed())->toBeFalse()
        ->and($response->error())->toBeNull();
});
