<?php

namespace Routegroup\Imoje\Payment\Tests\Lib;

use Illuminate\Http\Request;
use Routegroup\Imoje\Payment\Exceptions\InvalidSignatureException;
use Routegroup\Imoje\Payment\Exceptions\SchemaValidationException;
use Routegroup\Imoje\Payment\Lib\Validator;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\HashMethod;
use Routegroup\Imoje\Payment\Types\TransactionStatus;
use Routegroup\Imoje\Payment\Types\TransactionType;

beforeEach(function (): void {
    $this->validator = app(Validator::class);
});

it('validates notification successfully', function (): void {
    $response = [
        'transaction' => [
            'id' => '07938437-cae3-4d46-877d-e1b9d6e6c58f',
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

it('rejects a payload carrying none of the documented objects', function (): void {
    $this->validator->fromNotification(['anything' => 1]);
})->throws(SchemaValidationException::class);

it('rejects a transaction without an id', function (): void {
    $payload = notificationPayload();
    unset($payload['transaction']['id']);

    $this->validator->fromNotification($payload);
})->throws(SchemaValidationException::class);

it('validates a notification carrying only a payment profile', function (): void {
    $result = $this->validator->fromNotification([
        'paymentProfile' => [
            'id' => 'a1b2c3d4-0000-4000-8000-000000000000',
            'isActive' => true,
        ],
    ]);

    expect($result)->toBeTrue();
});

it('keeps an unknown top-level key alongside a documented one', function (): void {
    $result = $this->validator->fromNotification(
        notificationPayload() + ['somethingNew' => ['a' => 1]]
    );

    expect($result)->toBeTrue();
});

it('validates notification with only payment (e.g. link cancelled)', function (): void {
    $data = [
        'payment' => [
            'id' => '07980a69-a884-46f7-ad16-216c88a13b98',
            'title' => 'Order',
            'amount' => 100,
            'status' => 'cancelled',
            'created' => 1666339083,
            'orderId' => 'order-1',
            'currency' => 'PLN',
            'modified' => 1666339083,
            'serviceId' => 'a33f331b-23fc-42b0-9fd1-67f310028b46',
            'notificationUrl' => 'https://example.com/notification',
        ],
    ];

    $result = $this->validator->fromNotification($data);

    expect($result)->toBeTrue();
});

function notificationPayload(): array
{
    return [
        'transaction' => [
            'id' => '07938437-cae3-4d46-877d-e1b9d6e6c58f',
            'type' => 'sale',
            'status' => 'pending',
            'source' => 'api',
            'created' => 1666339083,
            'modified' => 1666339083,
            'notificationUrl' => 'https://example.com/notification',
            'serviceId' => 'a33f331b-23fc-42b0-9fd1-67f310028b46',
            'amount' => 100,
            'currency' => 'PLN',
            'title' => 'Zamówienie ćwierć',
            'orderId' => 'Zamowienie test',
            'paymentMethod' => 'pbl',
            'paymentMethodCode' => 'ipko',
        ],
    ];
}

function signedRequest(array $header = [], ?string $body = null): Request
{
    $rawBody = $body ?? json_encode(notificationPayload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $request = Request::create('/notification', 'POST', [], [], [], [], $rawBody);
    $request->headers->set('Content-Type', 'application/json; charset=UTF-8');

    $header = array_merge([
        'merchantid' => config('services.imoje.merchant_id'),
        'serviceid' => config('services.imoje.service_id'),
        'signature' => hash(HashMethod::SHA256->value, $rawBody.config('services.imoje.service_key')),
        'alg' => HashMethod::SHA256->value,
    ], $header);

    $request->headers->set(
        'X-Imoje-Signature',
        implode(';', array_map(fn (string $k, ?string $v): string => "$k=$v", array_keys($header), $header))
    );

    return $request;
}

it('accepts a correctly signed notification', function (): void {
    $this->validator->verifySignature(signedRequest());
})->expectNotToPerformAssertions();

it('accepts a signature over a body carrying diacritics', function (): void {
    $body = json_encode(notificationPayload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    expect($body)->toContain('ćwierć')
        ->and(fn () => $this->validator->verifySignature(signedRequest(body: $body)))
        ->not->toThrow(InvalidSignatureException::class);
});

it('rejects a tampered signature', function (): void {
    $this->validator->verifySignature(signedRequest([
        'signature' => str_repeat('0', 64),
    ]));
})->throws(InvalidSignatureException::class);

it('rejects a signature computed over a different body', function (): void {
    $request = signedRequest();
    $tampered = Request::create('/notification', 'POST', [], [], [], [], '{"transaction":{"amount":999}}');
    $tampered->headers->set('X-Imoje-Signature', (string) $request->headers->get('X-Imoje-Signature'));

    $this->validator->verifySignature($tampered);
})->throws(InvalidSignatureException::class);

it('rejects an unknown hashing algorithm', function (): void {
    $this->validator->verifySignature(signedRequest(['alg' => 'md5']));
})->throws(InvalidSignatureException::class);

it('rejects a missing hashing algorithm', function (): void {
    $request = Request::create('/notification', 'POST', [], [], [], [], '{"transaction":{}}');
    $request->headers->set('X-Imoje-Signature', 'merchantid=m;serviceid=s;signature=deadbeef');

    $this->validator->verifySignature($request);
})->throws(InvalidSignatureException::class);

it('rejects a notification with no signature header at all', function (): void {
    $this->validator->verifySignature(
        Request::create('/notification', 'POST', [], [], [], [], '{"transaction":{}}')
    );
})->throws(InvalidSignatureException::class);

it('rejects a notification with an empty body', function (): void {
    $request = new Request;

    $request->headers->set(
        'X-Imoje-Signature',
        'merchantid=m;serviceid=s;signature=b73321c9e8bcc414b8c08198db4084dafb1b4dc252f512ffe71b1fbce857fd23;alg=sha256'
    );

    $this->validator->verifySignature($request);
})->throws(InvalidSignatureException::class);

it('rejects a notification issued for another merchant', function (): void {
    $this->validator->verifySignature(signedRequest(['merchantid' => 'someone-else']));
})->throws(InvalidSignatureException::class);

it('rejects a notification issued for another service', function (): void {
    $this->validator->verifySignature(signedRequest(['serviceid' => 'someone-else']));
})->throws(InvalidSignatureException::class);
