<?php

namespace Routegroup\Imoje\Payment\Tests\Lib;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\Exceptions\ApiErrorException;
use Routegroup\Imoje\Payment\Lib\Api;

function fakeTransaction(mixed $body, int $status): Api
{
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetTransactionUrl('tx-1') => Http::response($body, $status),
    ]);

    return $api;
}

function envelope(string $code = 'TRX-ERROR-120101', string $message = 'Transaction in progress, cannot refund.'): array
{
    return ['apiErrorResponse' => ['code' => $code, 'message' => $message, 'errors' => ['amount' => 'too high']]];
}

it('keeps throwing on 422 and 500 with the flag off', function (int $status): void {
    expect(fn () => fakeTransaction(envelope(), $status)->getTransaction('tx-1'))
        ->toThrow(ApiErrorException::class);
})->with([[422], [500]]);

it('stays silent on the other codes with the flag off', function (int $status): void {
    $response = fakeTransaction(envelope(), $status)->getTransaction('tx-1');

    expect($response->failed())->toBeTrue();
})->with([[400], [401], [403], [404], [406], [503]]);

it('throws on every failing code once the flag is on', function (int $status): void {
    config()->set('services.imoje.strict_errors', true);

    expect(fn () => fakeTransaction(envelope(), $status)->getTransaction('tx-1'))
        ->toThrow(ApiErrorException::class);
})->with([[400], [401], [403], [404], [406], [422], [500], [503]]);

it('reports the real status instead of a constant 422', function (): void {
    config()->set('services.imoje.strict_errors', true);

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
    $error = fakeTransaction(envelope(), 404)->getTransaction('tx-1')->error();

    expect($error->code)->toBe('TRX-ERROR-120101')
        ->and($error->message)->toBe('Transaction in progress, cannot refund.')
        ->and($error->errors)->toBe(['amount' => 'too high']);
});

it('reads a bare body, the shape 400 and 401 arrive in', function (): void {
    $error = fakeTransaction(['message' => 'Cannot cancel payment'], 400)
        ->getTransaction('tx-1')->error();

    expect($error->message)->toBe('Cannot cancel payment')
        ->and($error->code)->toBeNull()
        ->and($error->errors)->toBe([]);
});

it('gives an array for errors even when the body carries none', function (): void {
    $error = fakeTransaction([], 401)->getTransaction('tx-1')->error();

    expect($error->errors)->toBe([]);
});

it('falls back to the reason phrase when a bare body carries no message', function (): void {
    config()->set('services.imoje.strict_errors', true);

    expect(fn () => fakeTransaction([], 401)->getTransaction('tx-1'))
        ->toThrow(ApiErrorException::class, 'Unauthorized');
});

it('reports success and no error on a 200', function (): void {
    $response = fakeTransaction(['transaction' => ['id' => 'tx-1']], 200)->getTransaction('tx-1');

    expect($response->failed())->toBeFalse()
        ->and($response->error())->toBeNull();
});
