<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Request\ChargeProfileRequestDto;
use Routegroup\Imoje\Payment\DTO\Request\RefundRequestDto;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Lib\Url;
use Routegroup\Imoje\Payment\Types\Currency;

beforeEach(function (): void {
    Http::fake();
    $this->api = app(Api::class);
    $this->url = app(Url::class);
});

it('makes refund request', function (): void {
    $dto = RefundRequestDto::make(['amount' => 100]);
    $this->api->createRefund($dto, '$transaction_id$');

    Http::assertSent(function (Request $request) {
        return $request->url() === $this->url->createRefundUrl('$transaction_id$')
            && $request->isJson()
            && $request->method() === 'POST'
            && array_keys($request->data()) === ['type', 'serviceId', 'amount'];
    });
});

it('makes charge profile request', function (): void {
    $dto = ChargeProfileRequestDto::make([
        'amount' => 1000 * 100,
        'currency' => Currency::PLN,
        'paymentProfileId' => '123456789',
        'orderId' => '1234',
    ]);

    $this->api->chargeProfile($dto);

    Http::assertSent(function (Request $request) {
        return $request->url() === $this->url->createChargeProfileUrl()
            && $request->isJson()
            && $request->method() === 'POST'
            && array_keys($request->data()) === ['serviceId', 'amount', 'currency', 'paymentProfileId', 'orderId'];
    });
});

it('makes deactivate profile request', function (): void {
    $this->api->deactivateProfile('$profile_id$');

    Http::assertSent(function (Request $request) {
        return $request->url() === $this->url->createProfileIdUrl('$profile_id$')
            && $request->isJson()
            && $request->method() === 'DELETE';
    });
});
