<?php

namespace Routegroup\Imoje\Payment\Tests\Lib;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\HashMethod;
use Routegroup\Imoje\Payment\Types\PaymentMethod;

beforeEach(function (): void {
    $this->utils = app(Utils::class);
});

it('creates signature')
    ->expect(fn () => $this->utils->createSignature([
        'bar' => 'baz',
        'foo' => 'faa',
    ]))
    ->toEqual('df8bdc2e4d0d93be22222ea57b5b0e196b897629e9089f05baadffee96baa90d;sha256');

it('verifies a signature over the raw body', function (): void {
    config(['services.imoje.service_key' => 'PIcMy86ssE5wuNHAuQn5zPKf6hCAwX3Oxvjw']);

    $body = '{"transaction":{"id":"tx-1"}}';
    $digest = hash('sha256', $body.config('services.imoje.service_key'));

    expect(app(Utils::class)->verifySignature($digest, $body, HashMethod::SHA256))->toBeTrue();
});

it('rejects a signature over a different body', function (): void {
    config(['services.imoje.service_key' => 'PIcMy86ssE5wuNHAuQn5zPKf6hCAwX3Oxvjw']);

    $digest = hash('sha256', '{"a":1}'.config('services.imoje.service_key'));

    expect(app(Utils::class)->verifySignature($digest, '{"a":2}', HashMethod::SHA256))->toBeFalse();
});

it('rejects a signature computed with another key', function (): void {
    config(['services.imoje.service_key' => 'ours']);

    $body = '{"a":1}';

    expect(app(Utils::class)->verifySignature(hash('sha256', $body.'theirs'), $body, HashMethod::SHA256))
        ->toBeFalse();
});

it('builds query for create signature')
    ->expect(fn () => $this->utils->buildQuery([
        'bar' => 'baz',
        'foo' => 'faa',
    ]))
    ->toEqual('bar=baz&foo=faa');

it('transforms values', function (
    array $value,
    array $result
): void {
    expect($this->utils->transformValues($value))->toEqual($result);
})->with([
    [
        [
            'bar' => 'baz',
            'foo' => 'faa',
        ],
        [
            'bar' => 'baz',
            'foo' => 'faa',
        ],
    ],
    [
        [
            'bar' => 'baz',
            'foo' => new class implements Arrayable
            {
                public function toArray(): array
                {
                    return ['bar' => 'baz'];
                }
            },
        ],
        [
            'bar' => 'baz',
            'foo' => ['bar' => 'baz'],
        ],
    ],
    [
        [
            'bar' => PaymentMethod::ING,
        ],
        [
            'bar' => PaymentMethod::ING->value,
        ],
    ],
]);

it('mock headers')->todo();

it('checks if contains structure')->todo();

/**
 * The reference implementation published in the paywall documentation,
 * chapter "Autoryzacja". Kept verbatim so the expectations below are pinned to
 * ING's algorithm rather than to a hand-typed string, which would encode the
 * same mistake twice.
 */
function referencePrepareData(array $data, string $prefix = ''): string
{
    ksort($data);
    $hashData = [];

    foreach ($data as $key => $value) {
        if ($prefix) {
            $key = $prefix.'['.$key.']';
        }

        $hashData[] = is_array($value) ? referencePrepareData($value, (string) $key) : $key.'='.$value;
    }

    return implode('&', $hashData);
}

/** The multipayout example from the paywall documentation. */
function multipayoutVector(): array
{
    return [
        'serviceId' => '63f574ed-d90d-4abe-9cs1-39117584a7b7',
        'merchantId' => '6yt3gjtm9p1odfgx8491',
        'amount' => '300',
        'currency' => 'PLN',
        'orderId' => '123',
        'orderDescription' => 'Example transaction',
        'customerFirstName' => 'John',
        'customerLastName' => 'Doe',
        'customerEmail' => 'johndoe@domain.com',
        'customerPhone' => '501501501',
        'urlSuccess' => 'https://your-shop.com/success',
        'urlFailure' => 'https://your-shop.com/failure',
        'urlReturn' => 'https://your-shop.com/return',
        'visibleMethod' => 'card,pbl',
        'multipayout' => [
            ['ban' => '72105000028166973380325415', 'amount' => '100', 'label' => 'Nazwa firmy 0'],
            ['ban' => '58105000025503268251444948', 'amount' => '200', 'label' => 'Nazwa firmy 1'],
        ],
    ];
}

it('matches the reference algorithm on the multipayout vector', function (): void {
    expect($this->utils->buildQuery(multipayoutVector()))
        ->toBe(referencePrepareData(multipayoutVector()));
});

it('expands a nested multipayout instead of flattening it to Array', function (): void {
    expect($this->utils->buildQuery(multipayoutVector()))
        ->toContain('multipayout[0][ban]=72105000028166973380325415')
        ->toContain('multipayout[1][label]=Nazwa firmy 1')
        ->not->toContain('multipayout=Array')
        ->not->toContain('=Array');
});

it('sorts nested keys at every level', function (): void {
    expect($this->utils->buildQuery([
        'billing' => ['lastName' => 'Kowalski', 'city' => 'Warszawa', 'firstName' => 'Jan'],
        'amount' => 100,
    ]))->toBe('amount=100&billing[city]=Warszawa&billing[firstName]=Jan&billing[lastName]=Kowalski');
});

it('signs exactly the parameters the request carries', function (array $data): void {
    $fromSignature = explode('&', $this->utils->buildQuery($data));
    $fromRequest = array_map('urldecode', explode('&', Arr::query($data)));

    sort($fromSignature);
    sort($fromRequest);

    expect($fromSignature)->toBe($fromRequest);
})->with([
    'flat' => [['bar' => 'baz', 'foo' => 'faa']],
    'nested' => [multipayoutVector()],
    'with nulls' => [['amount' => 100, 'validTo' => null, 'orderId' => '1', 'title' => null]],
]);

it('transforms enums and arrayables nested inside the payload', function (): void {
    expect($this->utils->buildQuery([
        'cart' => ['method' => PaymentMethod::CARD],
    ]))->toBe('cart[method]=card');
});
