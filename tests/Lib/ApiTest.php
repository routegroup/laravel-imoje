<?php

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\CancelPaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\CanRefundDto;
use Routegroup\Imoje\Payment\DTO\Api\ChargeProfileDto;
use Routegroup\Imoje\Payment\DTO\Api\DeactivateProfileDto;
use Routegroup\Imoje\Payment\DTO\Api\GetPaymentMethodsDto;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Api\PutTrustedIpsDto;
use Routegroup\Imoje\Payment\DTO\Api\RefundDto;
use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Responses\CancelPaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\CanRefundResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ChargeProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\GetPaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\GetTransactionResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\PaymentMethodsResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\PaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ProfileResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\RefundResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ServiceResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\ServicesResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TransactionResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TrustedIpsResponseDto;
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
    'successfully calls get transaction' => [
        fn (Api $api) => [
            $api->url->createGetTransactionUrl('$transaction_id$') => Http::response(TransactionResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getTransaction('$transaction_id$'),
        GetTransactionResponseDto::class,
    ],
    'successfully calls get payment' => [
        fn (Api $api) => [
            $api->url->createGetPaymentUrl('$payment_id$') => Http::response(CancelPaymentResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getPayment('$payment_id$'),
        GetPaymentResponseDto::class,
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
    'successfully calls capture transaction' => [
        fn (Api $api) => [
            $api->url->createCaptureUrl('$transaction_id$') => Http::response(TransactionResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->captureTransaction('$transaction_id$'),
        TransactionResponseDto::class,
    ],
    'successfully calls void transaction' => [
        fn (Api $api) => [
            $api->url->createVoidUrl('$transaction_id$') => Http::response(TransactionResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->voidTransaction('$transaction_id$'),
        TransactionResponseDto::class,
    ],
    'successfully calls get profile by cid' => [
        fn (Api $api) => [
            $api->url->createProfileCidUrl('$cid$') => Http::response(ProfileResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getProfileByCid('$cid$'),
        ProfileResponseDto::class,
    ],
    'successfully calls deactivate profile by post' => [
        fn (Api $api) => [
            $api->url->createProfileDeactivateUrl() => Http::response(ProfileResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->deactivateProfileByPost(DeactivateProfileDto::factory()->make()),
        ProfileResponseDto::class,
    ],
    'successfully calls can refund' => [
        fn (Api $api) => [
            $api->url->createCanRefundUrl('$transaction_id$') => Http::response(CanRefundResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->canRefund(CanRefundDto::factory()->make(), '$transaction_id$'),
        CanRefundResponseDto::class,
    ],
    'successfully calls get payment methods' => [
        fn (Api $api) => [
            $api->url->createGetPaymentMethodsUrl('$service_id$') => Http::response(PaymentMethodsResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getPaymentMethods('$service_id$', GetPaymentMethodsDto::factory()->make()),
        PaymentMethodsResponseDto::class,
    ],
    'successfully calls get service' => [
        fn (Api $api) => [
            $api->url->createServiceUrl('$service_id$') => Http::response(ServiceResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getService('$service_id$'),
        ServiceResponseDto::class,
    ],
    'successfully calls get services' => [
        fn (Api $api) => [
            $api->url->createServicesUrl() => Http::response(ServicesResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getServices(),
        ServicesResponseDto::class,
    ],
    'successfully calls get trusted ips' => [
        fn (Api $api) => [
            $api->url->createSettingsIpsUrl() => Http::response(TrustedIpsResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->getTrustedIps(),
        TrustedIpsResponseDto::class,
    ],
    'successfully calls put trusted ips' => [
        fn (Api $api) => [
            $api->url->createSettingsIpsUrl() => Http::response(TrustedIpsResponseDto::factory()->make()->toArray()),
        ],
        fn (Api $api) => $api->putTrustedIps(PutTrustedIpsDto::factory()->make()),
        TrustedIpsResponseDto::class,
    ],
]);
