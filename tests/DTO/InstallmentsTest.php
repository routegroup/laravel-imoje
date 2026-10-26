<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\InstallmentDto;
use Routegroup\Imoje\Payment\DTO\Paywall\InstallmentsCalculatorDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInstallmentException;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\Environment;
use Routegroup\Imoje\Payment\Types\PaymentMethodCode;

it('serves the calculator script from both environments', function (Environment $env, string $host): void {
    expect($env->installmentsUrl())->toBe("https://$host/js/installments.js");
})->with([
    [Environment::PRODUCTION, 'paywall.pay.ing.pl'],
    [Environment::SANDBOX, 'paywall.sandbox.pay.ing.pl'],
]);

it('accepts a period the gateway allows', function (int $period): void {
    expect((new InstallmentDto(['period' => $period]))->period)->toBe($period);
})->with([[3], [10], [36]]);

it('rejects a period outside the documented range', function (mixed $period): void {
    expect(fn () => new InstallmentDto(['period' => $period]))
        ->toThrow(InvalidInstallmentException::class);
})->with([
    'below the floor' => [2],
    'above the ceiling' => [37],
    'not whole' => [4.5],
    'not a number' => ['twelve'],
    'missing' => [null],
]);

it('holds a longer period against the channel that allows it', function (): void {
    $dto = new TransactionDto([
        'paymentMethodCode' => PaymentMethodCode::INBANK,
        'installment' => ['period' => 24],
    ]);

    expect($dto->installment->period)->toBe(24);
});

it('rejects a period the zero-interest channel does not allow', function (): void {
    expect(fn () => new TransactionDto([
        'paymentMethodCode' => PaymentMethodCode::INBANK_0,
        'installment' => ['period' => 24],
    ]))->toThrow(InvalidInstallmentException::class, 'must be at most 10 for inbank_0');
});

it('sends the period nested under installment', function (): void {
    $payload = (new TransactionDto([
        'paymentMethodCode' => PaymentMethodCode::INBANK,
        'installment' => ['period' => 12],
    ]))->toArray();

    expect($payload['installment'])->toBe(['period' => 12])
        ->and($payload)->not->toHaveKey('period')
        ->and($payload)->not->toHaveKey('channel');
});

it('signs the calculator options', function (): void {
    $dto = new InstallmentsCalculatorDto(['amount' => 50000]);
    $payload = $dto->toArray();

    $signed = $payload;
    unset($signed['signature']);

    [$hash] = explode(';', (string) $payload['signature']);

    expect($hash)->toBe(
        hash('sha256', app(Utils::class)->buildQuery($signed).config('services.imoje.service_key'))
    )->and($payload['currency'])->toBe(Currency::PLN->value);
});

it('rejects a calculator amount outside the documented bounds', function (mixed $amount): void {
    expect(fn () => new InstallmentsCalculatorDto(['amount' => $amount]))
        ->toThrow(InvalidInstallmentException::class);
})->with([
    'below 300 PLN' => [29999],
    'above 50000 PLN' => [5000001],
    'missing' => [null],
]);
