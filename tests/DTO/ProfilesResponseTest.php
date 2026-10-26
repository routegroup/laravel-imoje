<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Casts\PaymentProfileDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfilesResponseDto;
use Routegroup\Imoje\Payment\Lib\Api;

function profile(array $overrides = []): array
{
    return array_merge([
        'id' => 'f1e2d3c4-0000-4000-8000-000000000001',
        'merchantMid' => 'merchant-1',
        'merchantCustomerId' => 'cid-1',
        'maskedNumber' => '444444******4444',
        'month' => 12,
        'year' => 2030,
        'isActive' => true,
    ], $overrides);
}

function fetchProfiles(mixed $body, int $status = 200): ProfilesResponseDto
{
    $api = app(Api::class);
    Http::fake([$api->url->createProfileCidUrl('cid-1') => Http::response($body, $status)]);

    return $api->getProfilesByCid('cid-1');
}

it('hydrates the envelope the specification declares', function (): void {
    $response = fetchProfiles(['paymentProfiles' => [profile(), profile(['id' => 'second'])]]);

    expect($response->paymentProfiles)->toHaveCount(2)
        ->and($response->paymentProfiles[0])->toBeInstanceOf(PaymentProfileDto::class)
        ->and($response->paymentProfiles[0]->maskedNumber)->toBe('444444******4444')
        ->and($response->paymentProfiles[1]->id)->toBe('second');
});

/** /services answers with a bare list, so this endpoint may too. */
it('hydrates a bare list', function (): void {
    $response = fetchProfiles([profile(), profile()]);

    expect($response->paymentProfiles)->toHaveCount(2)
        ->and($response->paymentProfiles[0])->toBeInstanceOf(PaymentProfileDto::class)
        ->and($response->paymentProfiles[0]->merchantMid)->toBe('merchant-1');
});

/** /services wraps each item in its singular key; this would too. */
it('hydrates a bare list of wrapped items', function (): void {
    $response = fetchProfiles([['paymentProfile' => profile()]]);

    expect($response->paymentProfiles[0])->toBeInstanceOf(PaymentProfileDto::class)
        ->and($response->paymentProfiles[0]->maskedNumber)->toBe('444444******4444');
});

it('casts the scalars the singular DTO casts', function (): void {
    $profile = fetchProfiles(['paymentProfiles' => [profile(['month' => '12', 'isActive' => 1])]])
        ->paymentProfiles[0];

    expect($profile->month)->toBe(12)->and($profile->isActive)->toBeTrue();
});

/** The sandbox answers an unknown cid with 404 and an empty body. */
it('reads an unknown cid as a failure carrying no profiles', function (): void {
    $response = fetchProfiles(null, 404);

    expect($response->paymentProfiles)->toBe([])
        ->and($response->failed())->toBeTrue();
});

it('reads an empty list as a success carrying no profiles', function (): void {
    $response = fetchProfiles(['paymentProfiles' => []]);

    expect($response->paymentProfiles)->toBe([])
        ->and($response->failed())->toBeFalse();
});

it('keeps the singular endpoint on the singular DTO', function (): void {
    $api = app(Api::class);
    Http::fake([$api->url->createProfileIdUrl('p-1') => Http::response(['paymentProfile' => profile()])]);

    expect($api->getProfile('p-1'))->toBeInstanceOf(ProfileResponseDto::class);
});
