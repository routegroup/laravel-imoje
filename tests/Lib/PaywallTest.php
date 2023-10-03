<?php

use Illuminate\Http\RedirectResponse;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Routegroup\Imoje\Payment\Lib\Paywall;

beforeEach(function (): void {
    $this->paywall = app(Paywall::class);
});

it('creates transaction')
    ->expect(fn () => $this->paywall->createTransaction(new TransactionDto()))
    ->toBeInstanceOf(RedirectResponse::class);
