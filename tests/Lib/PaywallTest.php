<?php

namespace Routegroup\Imoje\Payment\Tests\Lib;

use Illuminate\Http\RedirectResponse;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Routegroup\Imoje\Payment\Lib\Paywall;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;

beforeEach(function (): void {
    $this->paywall = app(Paywall::class);
});

it('creates transaction')
    ->expect(fn () => $this->paywall->createTransaction(TransactionDto::factory()->make()))
    ->toBeInstanceOf(RedirectResponse::class);

it('redirects to the gateway carrying a nested billing address', function (): void {
    $dto = new TransactionDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'order-1',
        'billing' => [
            'lastName' => 'Kowalski',
            'firstName' => 'Jan',
            'city' => 'Warszawa',
        ],
    ]);

    $query = [];
    parse_str(
        (string) parse_url($this->paywall->createTransaction($dto)->getTargetUrl(), PHP_URL_QUERY),
        $query
    );

    expect($query['billing'])->toBe([
        'lastName' => 'Kowalski',
        'firstName' => 'Jan',
        'city' => 'Warszawa',
    ]);
});

it('signs the nested payload the gateway actually receives', function (): void {
    $dto = new TransactionDto([
        'amount' => 1000,
        'currency' => Currency::PLN,
        'orderId' => 'order-1',
        'billing' => ['firstName' => 'Jan', 'city' => 'Warszawa'],
    ]);

    $query = [];
    parse_str(
        (string) parse_url($this->paywall->createTransaction($dto)->getTargetUrl(), PHP_URL_QUERY),
        $query
    );

    $received = $query;
    unset($received['signature']);

    [$hash] = explode(';', (string) $query['signature']);

    expect($hash)->toBe(
        hash('sha256', app(Utils::class)->buildQuery($received).config('services.imoje.service_key'))
    );
});
