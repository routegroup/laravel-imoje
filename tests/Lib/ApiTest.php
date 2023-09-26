<?php

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Requests\ChargeProfileRequestDto;
use Routegroup\Imoje\Payment\DTO\Requests\RefundRequestDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;
use Routegroup\Imoje\Payment\Lib\Api;

beforeEach(function (): void {
    $this->api = app(Api::class);
});

it('tests api requests and responses', function (
    callable $mockResponse,
    callable $mockRequest,
    string $instance,
): void {
    Http::fake($mockResponse($this->api));
    $response = $mockRequest($this->api);
    expect($response)->toBeInstanceOf($instance);
})->with([
    'successfully calls refund' => [
        fn (Api $api) => [
            $api->url->createRefundUrl('$transaction_id$') => Http::response(RefundResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->createRefund(RefundRequestDto::factory()->make(), '$transaction_id$'),
        RefundResponseDto::class,
    ], 
    'successfully calls get profile' => [
        fn (Api $api) => [
            $api->url->createProfileIdUrl('$profile_id$') => Http::response(ProfileResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getProfile('$profile_id$'),
        ProfileResponseDto::class,
    ],
    'successfully calls charge profile' => [
        fn (Api $api) => [
            $api->url->createChargeProfileUrl() => Http::response(ChargeProfileResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->chargeProfile(ChargeProfileRequestDto::factory()->make()),
        ChargeProfileResponseDto::class,
    ],
    'successfully calls deactivate profile' => [
        fn (Api $api) => [
            $api->url->createProfileIdUrl('$profile_id$') => Http::response(ProfileResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->deactivateProfile('$profile_id$'),
        ProfileResponseDto::class,
    ],
]);
