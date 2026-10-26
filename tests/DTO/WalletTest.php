<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\AdditionalDataDto;
use Routegroup\Imoje\Payment\DTO\Casts\ApplePayHeaderDto;
use Routegroup\Imoje\Payment\DTO\Casts\BrowserDto;
use Routegroup\Imoje\Payment\DTO\Casts\WalletDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidWalletException;

/** The browser block from the Google Pay and Apple Pay chapter, verbatim. */
function chapterBrowser(array $overrides = []): array
{
    return array_replace([
        'ip' => '127.0.0.1',
        'language' => 'pl-PL',
        'jsEnabled' => true,
        'timezoneOffset' => 100,
        'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36',
        'accept' => 'application/json, text/javascript, */*; q=0.01',
        'javaEnabled' => false,
        'screenColorDepth' => 24,
        'screenHeight' => 1080,
        'screenWidth' => 2560,
    ], $overrides);
}

function walletTransaction(array $overrides): array
{
    return (new TransactionDto(array_replace([
        'amount' => 1000,
        'currency' => 'PLN',
        'orderId' => 'yourOrderId',
        'customer' => ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john.doe@example.com'],
        'successReturnUrl' => 'https://yourshopname.com/success',
        'failureReturnUrl' => 'https://yourshopname.com/failure',
        'paymentMethod' => 'wallet',
        'additionalData' => ['browser' => chapterBrowser()],
    ], $overrides)))->toArray();
}

it('builds the Google Pay request from the chapter', function (): void {
    $payload = walletTransaction([
        'paymentMethodCode' => 'gpay',
        'wallet' => [
            'signature' => 'signature',
            'protocolVersion' => 'protocolVersion',
            'signedMessage' => 'signedMessage',
        ],
    ]);

    expect($payload['paymentMethod'])->toBe('wallet')
        ->and($payload['paymentMethodCode'])->toBe('gpay')
        ->and($payload['wallet'])->toBe([
            'signature' => 'signature',
            'protocolVersion' => 'protocolVersion',
            'signedMessage' => 'signedMessage',
        ])
        ->and($payload['additionalData'])->toBe(['browser' => chapterBrowser()]);
});

it('builds the Apple Pay request from the chapter', function (): void {
    $transactionId = str_repeat('a', 64);

    $payload = walletTransaction([
        'paymentMethodCode' => 'applepay',
        'wallet' => ['paymentData' => [
            'version' => 'version',
            'data' => 'data',
            'signature' => 'signature',
            'header' => [
                'ephemeralPublicKey' => 'ephemeralPublicKey',
                'publicKeyHash' => 'publicKeyHash',
                'transactionId' => $transactionId,
            ],
        ]],
    ]);

    expect($payload['paymentMethodCode'])->toBe('applepay')
        ->and($payload['wallet'])->toBe(['paymentData' => [
            'version' => 'version',
            'data' => 'data',
            'signature' => 'signature',
            'header' => [
                'ephemeralPublicKey' => 'ephemeralPublicKey',
                'publicKeyHash' => 'publicKeyHash',
                'transactionId' => $transactionId,
            ],
        ]]);
});

it('leaves no Google Pay keys on an Apple Pay wallet', function (): void {
    $wallet = new WalletDto(['paymentData' => [
        'version' => 'v', 'data' => 'd', 'signature' => 's',
        'header' => ['ephemeralPublicKey' => 'e', 'publicKeyHash' => 'p', 'transactionId' => str_repeat('a', 64)],
    ]]);

    expect(array_keys($wallet->toArray()))->toBe(['paymentData']);
});

/** The gateway types these strictly: '100' and 'true' are refused outright. */
it('coerces the browser scalars the gateway insists on', function (): void {
    $browser = new BrowserDto(chapterBrowser([
        'jsEnabled' => 'true', 'javaEnabled' => 0, 'timezoneOffset' => '100',
        'screenColorDepth' => '24', 'screenHeight' => '1080', 'screenWidth' => '2560',
    ]));

    expect($browser->toArray())->toBe(chapterBrowser());
});

it('demands every browser field', function (string $field): void {
    $browser = chapterBrowser();
    unset($browser[$field]);

    expect(fn () => new BrowserDto($browser))
        ->toThrow(InvalidWalletException::class, "additionalData.browser.$field");
})->with([
    'ip', 'language', 'jsEnabled', 'timezoneOffset', 'userAgent',
    'accept', 'javaEnabled', 'screenColorDepth', 'screenHeight', 'screenWidth',
]);

/** Without the explicit check the cast still throws, but blames browser.ip for a missing block. */
it('names the missing block rather than its first field', function (): void {
    expect(fn () => new AdditionalDataDto([]))
        ->toThrow(InvalidWalletException::class, 'additionalData.browser: is required');
});

it('demands every Google Pay field', function (string $field): void {
    $wallet = ['signature' => 's', 'protocolVersion' => 'p', 'signedMessage' => 'm'];
    unset($wallet[$field]);

    expect(fn () => new WalletDto($wallet))->toThrow(InvalidWalletException::class, "wallet.$field");
})->with(['signature', 'protocolVersion', 'signedMessage']);

it('demands every Apple Pay field', function (string $field): void {
    $data = ['version' => 'v', 'data' => 'd', 'signature' => 's',
        'header' => ['ephemeralPublicKey' => 'e', 'publicKeyHash' => 'p', 'transactionId' => str_repeat('a', 64)]];
    unset($data[$field]);

    expect(fn () => new WalletDto(['paymentData' => $data]))
        ->toThrow(InvalidWalletException::class, "wallet.paymentData.$field");
})->with(['version', 'data', 'signature', 'header']);

it('demands every Apple Pay header field', function (string $field): void {
    $header = ['ephemeralPublicKey' => 'e', 'publicKeyHash' => 'p', 'transactionId' => str_repeat('a', 64)];
    unset($header[$field]);

    expect(fn () => new ApplePayHeaderDto($header))
        ->toThrow(InvalidWalletException::class, "wallet.paymentData.header.$field");
})->with(['ephemeralPublicKey', 'publicKeyHash', 'transactionId']);

/** The gateway puts minLength 64 on it, and answers 422 below that. */
it('demands a transaction id of the length the gateway wants', function (): void {
    expect(fn () => new ApplePayHeaderDto([
        'ephemeralPublicKey' => 'e', 'publicKeyHash' => 'p', 'transactionId' => str_repeat('a', 63),
    ]))->toThrow(InvalidWalletException::class, 'at least 64 characters');
});

it('types the wallet and the browser data on a transaction', function (): void {
    $dto = new TransactionDto([
        'amount' => 1000, 'currency' => 'PLN', 'orderId' => 'o',
        'customer' => ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john.doe@example.com'],
        'successReturnUrl' => 'https://a.pl/ok', 'failureReturnUrl' => 'https://a.pl/no',
        'paymentMethod' => 'wallet', 'paymentMethodCode' => 'gpay',
        'wallet' => ['signature' => 's', 'protocolVersion' => 'p', 'signedMessage' => 'm'],
        'additionalData' => ['browser' => chapterBrowser()],
    ]);

    expect($dto->wallet)->toBeInstanceOf(WalletDto::class)
        ->and($dto->additionalData)->toBeInstanceOf(AdditionalDataDto::class)
        ->and($dto->additionalData->browser)->toBeInstanceOf(BrowserDto::class)
        ->and($dto->additionalData->browser->timezoneOffset)->toBe(100);
});
