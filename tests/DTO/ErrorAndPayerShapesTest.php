<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Illuminate\Support\Facades\Http;
use Routegroup\Imoje\Payment\DTO\Casts\ApiErrorDto;
use Routegroup\Imoje\Payment\DTO\Casts\PayerCardDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\PayerDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Responses\ApiErrorResponseDto;
use Routegroup\Imoje\Payment\Exceptions\ApiErrorException;
use Routegroup\Imoje\Payment\Lib\Api;

/** A 422 body as the sandbox sends it, random three-character keys included. */
function validationBody(): array
{
    return ['apiErrorResponse' => [
        'code' => 'REQ-ERROR-100002',
        'message' => 'Unprocessable Entity.',
        'errors' => [
            ['property' => 'instance', 'message' => 'requires property "amount"', 'h8Q' => '5xN'],
            ['property' => 'instance', 'message' => 'requires property "currency"', 'dBi' => 'mwf'],
        ],
    ]];
}

/** A 422 throws, which is the path a consumer actually meets. */
function errorOf(array $body, int $status = 422): ApiErrorResponseDto
{
    $api = app(Api::class);
    Http::fake([$api->url->createGetTransactionUrl('tx-1') => Http::response($body, $status)]);

    try {
        return $api->getTransaction('tx-1')->error();
    } catch (ApiErrorException $e) {
        return $e->dto;
    }
}

it('hydrates each validation error', function (): void {
    $errors = errorOf(validationBody())->errors;

    expect($errors)->toHaveCount(2)
        ->and($errors[0])->toBeInstanceOf(ApiErrorDto::class)
        ->and($errors[0]->property)->toBe('instance')
        ->and($errors[0]->message)->toBe('requires property "amount"')
        ->and($errors[1]->message)->toBe('requires property "currency"');
});

/** Fluent is an ArrayAccess, so code written against the old raw arrays keeps working. */
it('still reads an error like the array it used to be', function (): void {
    $errors = errorOf(validationBody())->errors;

    expect($errors[0]['message'])->toBe('requires property "amount"')
        ->and($errors[0]['property'])->toBe('instance');
});

it('carries the gateway’s junk key rather than dropping it', function (): void {
    expect(errorOf(validationBody())->errors[0]->toArray())->toHaveKey('h8Q');
});

it('leaves a shape the gateway has never sent alone', function (): void {
    $errors = errorOf(['apiErrorResponse' => ['errors' => ['amount' => 'too high']]])->errors;

    expect($errors)->toBe(['amount' => 'too high']);
});

it('gives an empty list when the body carries no errors', function (): void {
    expect(errorOf(['apiErrorResponse' => ['code' => 'X']])->errors)->toBe([]);
});

it('types the payer behind a transfer', function (): void {
    $transaction = new TransactionDto([
        'id' => 'tx-1',
        'payerData' => ['name' => 'Jan Kowalski', 'account' => '39109024028943913168514519'],
    ]);

    expect($transaction->payerData)->toBeInstanceOf(PayerDataDto::class)
        ->and($transaction->payerData->name)->toBe('Jan Kowalski');
});

it('types the card behind a payment', function (): void {
    $transaction = new TransactionDto([
        'id' => 'tx-1',
        'payerCardData' => [
            'brand' => 'visa', 'bin' => '444444', 'last4' => '4444', 'type' => 'debit',
            'countryCodeAlpha2' => 'PL', 'countryCodeAlpha3' => 'POL', 'countryName' => 'Poland',
            'firstTransactionId' => 'tx-0',
        ],
    ]);

    expect($transaction->payerCardData)->toBeInstanceOf(PayerCardDataDto::class)
        ->and($transaction->payerCardData->last4)->toBe('4444')
        ->and($transaction->payerCardData->countryName)->toBe('Poland');
});

/** Both are absent until a payment completes, so an unpaid transaction must not invent them. */
it('leaves the payer fields out when the gateway omits them', function (): void {
    $payload = (new TransactionDto(['id' => 'tx-1']))->toArray();

    expect($payload)->not->toHaveKey('payerData')
        ->and($payload)->not->toHaveKey('payerCardData');
});

/** The specification defines no element shape for it, and no probe can reach one. */
it('leaves refunds a plain array', function (): void {
    $transaction = new TransactionDto(['id' => 'tx-1', 'refunds' => [['amount' => 100]]]);

    expect($transaction->refunds)->toBe([['amount' => 100]]);
});
