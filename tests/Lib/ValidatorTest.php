<?php

use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\Exceptions\InvalidSignatureException;
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

it('verifies signature', function (): void {
    $request = new Request([
        'transaction' => [
            'id' => '07938437-cae3-4d46-877d-e1b9d6e6c58f',
            'type' => 'sale',
            'status' => 'pending',
            'source' => 'api',
            'created' => 1666339083,
            'modified' => 1666339083,
            'notificationUrl' => 'https://qaz54.requestcatcher.com/',
            'serviceId' => 'a33f331b-23fc-42b0-9fd1-67f310028b46',
            'amount' => 100,
            'currency' => 'PLN',
            'title' => '',
            'orderId' => 'Zamowienie test',
            'paymentMethod' => 'pbl',
            'paymentMethodCode' => 'ipko',
        ],
        'payment' => [
            'id' => '07980a69-a884-46f7-ad16-216c88a13b98',
            'title' => '',
            'amount' => 100,
            'status' => 'pending',
            'created' => 1666339083,
            'orderId' => 'Zamowienie test',
            'currency' => 'PLN',
            'modified' => 1666339083,
            'serviceId' => 'a33f331b-23fc-42b0-9fd1-67f310028b46',
            'notificationUrl' => 'https://qaz54.requestcatcher.com/',
        ],
        'action' => [
            'type' => 'redirect',
            'url' => 'https://sandbox.paywall.imoje.pl/sandbox/07980a69-a884-46f7-ad16-216c88a13b98',
            'method' => 'GET',
            'contentType' => '',
            'contentBodyRaw' => '',
        ],
    ]);

    $request->headers->set(
        'X-Imoje-Signature',
        'merchantid=mdy7zxvxudgarxbsou9n;serviceid=a33f331b-23fc-42b0-9fd1-67f310028b46;signature=b73321c9e8bcc414b8c08198db4084dafb1b4dc252f512ffe71b1fbce857fd23;alg=sha256'
    );

    config(['services.imoje.service_key' => 'PIcMy86ssE5wuNHAuQn5zPKf6hCAwX3Oxvjw']);
    $validator = app(Validator::class);
    $validator->verifySignature($request);
})->expectNotToPerformAssertions();

it('expects to throw InvalidSignatureException', function (): void {
    $request = new Request;

    $request->headers->set(
        'X-Imoje-Signature',
        'merchantid=mdy7zxvxudgarxbsou9n;serviceid=a33f331b-23fc-42b0-9fd1-67f310028b46;signature=b73321c9e8bcc414b8c08198db4084dafb1b4dc252f512ffe71b1fbce857fd23;alg=sha256'
    );

    $this->validator->verifySignature($request);
})->throws(InvalidSignatureException::class);
