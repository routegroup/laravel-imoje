<?php

use Illuminate\Http\RedirectResponse;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Routegroup\Imoje\Payment\Lib\Paywall;

beforeEach(function (): void {
    $this->paywall = app(Paywall::class);
});

it('creates signature')
    ->expect(fn () => $this->paywall->createSignature([
        'bar' => 'baz',
        'foo' => 'faa',
    ]))
    ->toEqual('df8bdc2e4d0d93be22222ea57b5b0e196b897629e9089f05baadffee96baa90d;sha256');

it('builds query for create signature')
    ->expect(fn () => $this->paywall->buildQuery([
        'bar' => 'baz',
        'foo' => 'faa',
    ]))
    ->toEqual('bar=baz&foo=faa');

it('creates transaction')
    ->expect(fn () => $this->paywall->createTransaction(new TransactionDto()))
    ->toBeInstanceOf(RedirectResponse::class);
