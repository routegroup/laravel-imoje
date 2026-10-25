<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\GetPaymentMethodsDto;
use Routegroup\Imoje\Payment\DTO\Responses\PaymentMethodsResponseDto;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Types\Currency;

function methodRows(): array
{
    return [
        [
            'label' => 'Płatność BLIK',
            'isOnline' => true,
            'image' => [['png' => 'https://cdn.pay.ing.pl/img/pay/blik.png', 'dzP' => 'NNp']],
            'channels' => [
                ['paymentMethod' => 'blik', 'paymentMethodCode' => 'blik', 'priority' => 1],
            ],
            'priority' => 1,
            '24M' => 'yTN',
        ],
    ];
}

function fetchMethods(array $body): PaymentMethodsResponseDto
{
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetPaymentMethodsUrl('service-1') => Http::response($body),
    ]);

    return $api->getPaymentMethods('service-1', new GetPaymentMethodsDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
    ]));
}

it('unwraps the envelope the gateway actually sends', function (): void {
    $response = fetchMethods(['timestamp' => 1792953644448, 'data' => methodRows()]);

    expect($response->methods)->toBe(methodRows())
        ->and($response->timestamp)->toBe(1792953644448);
});

it('accepts the bare list the specification documents', function (): void {
    $response = fetchMethods(methodRows());

    expect($response->methods)->toBe(methodRows())
        ->and($response->timestamp)->toBeNull();
});

it('keeps the random keys the gateway injects', function (): void {
    $response = fetchMethods(['timestamp' => 1, 'data' => methodRows()]);

    expect($response->methods[0])->toHaveKey('24M')
        ->and($response->methods[0]['image'][0])->toHaveKey('dzP');
});

it('yields an empty list for a body that is neither shape', function (): void {
    $response = fetchMethods(['unexpected' => true]);

    expect($response->methods)->toBe([])
        ->and($response->timestamp)->toBeNull();
});

it('builds the enveloped shape from its factory', function (): void {
    $response = PaymentMethodsResponseDto::factory()->make();

    expect($response->methods)->toBeArray()
        ->and($response->methods)->toHaveCount(2)
        ->and($response->timestamp)->toBeInt()
        ->and($response->methods[0]['channels'][0]['paymentMethodCode'])->toBe('blik')
        ->and($response->methods[1]['channels'][1]['paymentMethodCode'])->toBe('wt_split');
});
