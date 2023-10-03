<?php

use Routegroup\Imoje\Payment\Lib\Utils;

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

it('transforms values')->todo();

it('checks if contains a structure')->todo();
