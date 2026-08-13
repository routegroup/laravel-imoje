<?php

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\CustomerDto;
use Routegroup\Imoje\Payment\DTO\Responses\CancelPaymentResponseDto;
use Routegroup\Imoje\Payment\DTO\Responses\TransactionResponseDto;
use Routegroup\Imoje\Payment\Lib\Api;
use Routegroup\Imoje\Payment\Types\Currency;

function getPaymentResponsePayload(array $overrides = []): array
{
    return array_replace_recursive(
        CancelPaymentResponseDto::factory()->make()->toArray(),
        $overrides,
    );
}

it('omits customer email from a payment request when it is not provided', function (): void {
    $dto = new PaymentDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'order-1',
        'customer' => new CustomerDto([
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
        ]),
    ]);

    expect($dto->toArray()['customer'])
        ->not->toHaveKey('email')
        ->toMatchArray([
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
        ]);
});

it('hydrates a GET payment amount sent as a string to grosze', function (): void {
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetPaymentUrl('$payment_id$') => Http::response(
            getPaymentResponsePayload(['amount' => '10000']),
        ),
    ]);

    $response = $api->getPayment('$payment_id$');

    expect($response->amount)->toBe(10000);
});

it('hydrates a GET payment when optional status is omitted', function (): void {
    $api = app(Api::class);
    $payload = getPaymentResponsePayload();
    unset($payload['status']);

    Http::fake([
        $api->url->createGetPaymentUrl('$payment_id$') => Http::response($payload),
    ]);

    $response = $api->getPayment('$payment_id$');

    expect($response->status)->toBeNull()
        ->and($response->amount)->toBeInt();
});

it('hydrates a GET payment when customer email is omitted', function (): void {
    $api = app(Api::class);
    $payload = getPaymentResponsePayload();
    unset($payload['customer']['email']);

    Http::fake([
        $api->url->createGetPaymentUrl('$payment_id$') => Http::response($payload),
    ]);

    $response = $api->getPayment('$payment_id$');

    expect($response->customer->toArray())->not->toHaveKey('email');
});

it('keeps undocumented GET payment properties', function (): void {
    $api = app(Api::class);

    Http::fake([
        $api->url->createGetPaymentUrl('$payment_id$') => Http::response(
            getPaymentResponsePayload([
                'trackingId' => 'trk-1',
            ]),
        ),
    ]);

    $response = $api->getPayment('$payment_id$');

    expect($response->trackingId)->toBe('trk-1');
});

it('hydrates a GET transaction when action type is omitted', function (): void {
    $api = app(Api::class);
    $payload = TransactionResponseDto::factory()->make()->toArray();
    unset($payload['action']['type']);

    Http::fake([
        $api->url->createGetTransactionUrl('$transaction_id$') => Http::response($payload),
    ]);

    $response = $api->getTransaction('$transaction_id$');

    expect($response->action->type)->toBeNull()
        ->and($response->action->url)->toBeString();
});
