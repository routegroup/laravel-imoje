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

it('builds query for create signature')
    ->expect(fn () => $this->utils->buildQuery([
        'bar' => 'baz',
        'foo' => 'faa',
    ]))
    ->toEqual('bar=baz&foo=faa');

it('creates refund url')
    ->expect(fn () => $this->utils->createRefundUrl('$transaction_id$'))
    ->toEqual('https://sandbox.api.imoje.pl/v1/merchant/$merchant_id$/transaction/$transaction_id$/refund');

it('creates charge profile url')
    ->expect(fn () => $this->utils->createChargeProfileUrl())
    ->toEqual('https://sandbox.api.imoje.pl/v1/merchant/$merchant_id$/transaction/profile');

it('creates deactivate profile url')
    ->expect(fn () => $this->utils->createDeactivateProfileUrl('$profile_id$'))
    ->toEqual('https://sandbox.api.imoje.pl/v1/merchant/$merchant_id$/profile/id/$profile_id$');
