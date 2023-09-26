<?php

use Routegroup\Imoje\Payment\Exceptions\SchemaValidationException;
use Routegroup\Imoje\Payment\Lib\Validator;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

beforeEach(function (): void {
    $this->validator = app(Validator::class);
});

it('validates notification successfully', function (): void {
    $response = [
        'transaction' => [
            'amount' => 1000,
            'currency' => Currency::EUR->value,
            'status' => TransactionStatus::SETTLED->value,
            'orderId' => '96e0e03a-c8d9-4ae6-90a6-10a58301cb5e',
            'serviceId' => '291bd8de-9f16-478e-b337-3d2e5a874926',
            'type' => TransactionType::SALE->value,
        ],
    ];

    $result = $this->validator->fromNotification($response);

    expect($result)->toBeTrue();
});

it('validates notification and throws an exception', function (): void {
    $this->validator->fromNotification([]);
})->throws(SchemaValidationException::class);
