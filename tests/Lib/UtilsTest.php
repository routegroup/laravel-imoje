<?php

use Illuminate\Contracts\Support\Arrayable;
use Routegroup\Imoje\Payment\Lib\Utils;
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

it('verifies signature')->skip('Tested in ValidatorTest');

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
