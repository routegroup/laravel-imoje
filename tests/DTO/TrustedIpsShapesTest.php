<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\PutTrustedIpsDto;
use Routegroup\Imoje\Payment\DTO\Responses\TrustedIpsResponseDto;
use Routegroup\Imoje\Payment\Lib\Api;

function fakeIps(array $body): Api
{
    $api = app(Api::class);

    Http::fake([$api->url->createSettingsIpsUrl() => Http::response($body)]);

    return $api;
}

it('reads the list GET answers with', function (): void {
    $response = fakeIps(['trustedIps' => ['127.0.0.1', '10.10.10.10']])->getTrustedIps();

    expect($response->trustedIps)->toBe(['127.0.0.1', '10.10.10.10'])
        ->and($response->status)->toBeNull();
});

it('reads the status PUT answers with', function (): void {
    $response = fakeIps(['status' => 'success'])
        ->putTrustedIps(PutTrustedIpsDto::factory()->make());

    expect($response->status)->toBe('success')
        ->and($response->trustedIps)->toBeNull();
});

it('keeps an empty list as an empty list', function (): void {
    expect(fakeIps(['trustedIps' => []])->getTrustedIps()->trustedIps)->toBe([]);
});

it('reports no list at all after a put', function (): void {
    expect(fakeIps(['status' => 'success'])->putTrustedIps(PutTrustedIpsDto::factory()->make())->trustedIps)
        ->toBeNull();
});

it('keeps the list an array rather than the null the dead cast produced', function (): void {
    expect(fakeIps(['trustedIps' => ['127.0.0.1']])->getTrustedIps()->trustedIps)
        ->toBeArray();
});

it('builds both shapes from the factory', function (): void {
    $get = TrustedIpsResponseDto::factory()->make()->toArray();
    $put = TrustedIpsResponseDto::factory()->afterPut()->make()->toArray();

    expect($get)->toHaveKey('trustedIps')->not->toHaveKey('status')
        ->and($put)->toHaveKey('status')->not->toHaveKey('trustedIps')
        ->and($put['status'])->toBe('success');
});

it('carries no phantom list key after a put', function (): void {
    $payload = fakeIps(['status' => 'success'])
        ->putTrustedIps(PutTrustedIpsDto::factory()->make())
        ->toArray();

    expect($payload)->toHaveKey('status')
        ->and($payload)->not->toHaveKey('trustedIps');
});
