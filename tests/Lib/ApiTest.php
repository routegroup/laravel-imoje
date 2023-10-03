<?php

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\CancelPaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\ChargeProfileDto;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\RefundDto;
use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Responses\CancelPaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\PaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TransactionResponseDto;
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
    'successfully calls create transaction' => [
        fn (Api $api) => [
            $api->url->createTransactionUrl() => Http::response(TransactionResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->createTransaction(TransactionDto::factory()->make()),
        TransactionResponseDto::class,
    ],
    'successfully calls create payment' => [
        fn (Api $api) => [
            $api->url->createPaymentUrl() => Http::response(PaymentResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->createPayment(PaymentDto::factory()->make()),
        PaymentResponseDto::class,
    ],
    'successfully calls cancel payment' => [
        fn (Api $api) => [
            $api->url->createCancelPaymentUrl() => Http::response(CancelPaymentResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->cancelPayment(CancelPaymentDto::factory()->make()),
        CancelPaymentResponseDto::class,
    ],
    'successfully calls refund' => [
        fn (Api $api) => [
            $api->url->createRefundUrl('$transaction_id$') => Http::response(RefundResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->createRefund(RefundDto::factory()->make(), '$transaction_id$'),
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
        fn (Api $api) => $api->chargeProfile(ChargeProfileDto::factory()->make()),
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
