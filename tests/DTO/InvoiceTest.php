<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\TransactionDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto as PaywallTransactionDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInvoiceException;
use Routegroup\Imoje\Payment\Lib\Utils;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\InvoiceBuyerType;
use Routegroup\Imoje\Payment\Types\InvoiceIdType;
use Routegroup\Imoje\Payment\Types\TaxStake;

/** The example from the Split Payment chapter, verbatim. */
function specInvoice(array $overrides = []): array
{
    return array_replace_recursive([
        'invoiceId' => '04/12/23',
        'buyer' => [
            'type' => 'COMPANY',
            'idType' => 'VAT_ID',
            'idNumber' => '5354235387',
            'email' => 'nazwafirmy@example.com',
            'fullName' => 'Nazwa firmy',
            'street' => 'Ulica',
            'city' => 'Miasto',
            'postalCode' => '12-345',
            'countryCodeAlpha2' => 'PL',
            'idCountryCodeAlpha2' => 'PL',
        ],
        'positions' => [[
            'name' => 'Produkt',
            'code' => 'produkt-01',
            'quantity' => 1,
            'unit' => 'Sztuki',
            'taxStake' => 'TAX_23',
            'grossAmount' => 12300,
            'taxAmount' => 2300,
        ]],
    ], $overrides);
}

it('keeps invoiceId beside buyer, where the gateway puts it', function (): void {
    $payload = (new InvoiceDto(specInvoice()))->toArray();

    expect(array_keys($payload))->toBe(['invoiceId', 'buyer', 'positions'])
        ->and($payload['buyer'])->not->toHaveKey('invoiceId');
});

it('round-trips through the gateway encoding', function (): void {
    $encoded = (new InvoiceDto(specInvoice()))->encoded();
    $decoded = json_decode((string) gzdecode((string) base64_decode($encoded)), true);

    expect($decoded['invoiceId'])->toBe('04/12/23')
        ->and($decoded['buyer']['idNumber'])->toBe('5354235387')
        ->and($decoded['positions'][0]['taxStake'])->toBe('TAX_23');
});

it('decodes to the same structure as the published example', function (): void {
    $published = 'H4sIAAAAAAAACm2QXU+DMBSG/4rp9eaAQjRcSfBmiUOSodE4s3TQaUM/EE51jPDfPe3inZfneT/6phMR+tuImq8bkpIg3q3CaLeKKFmQgx15T9KJwNhxFPPHTZkVr6iIprqg56zar+89Kaw6ODtJaBJHNKG3N8i5YkIi1Oz8w46iV+MdPzHVSX5dG4WGo5WyYMqVFc5z5U0oDNBzDoifpKgZglrAiOdGsAEM3p0ZgMncNC4bRksaJ85lrIZ+dDiT3SeLUCwf/ML8f2n2VQKE0QNJ3ybc6ueUvWlsC77Tv9FdwDIIkX1ZpsEvChfEauGWbs9gW4EisNMWWOtCVfay97/50ZthyJTbgJmIBoH3/REH5vf5FyK2JcWPAQAA';

    $theirs = json_decode((string) gzdecode((string) base64_decode($published)), true);
    $ours = json_decode((string) gzdecode((string) base64_decode((new InvoiceDto(specInvoice()))->encoded())), true);

    expect($ours)->toBe($theirs);
});

it('accepts a tax rate given as a bare percentage', function (): void {
    $invoice = new InvoiceDto(specInvoice(['positions' => [['taxStake' => 23]]]));

    expect($invoice->positions[0]->taxStake)->toBe(TaxStake::TAX_23);
});

it('rejects a tax rate the gateway does not know', function (): void {
    expect(fn () => new InvoiceDto(specInvoice(['positions' => [['taxStake' => 17]]])))
        ->toThrow(InvalidInvoiceException::class);
});

it('requires an identifier for a company buyer', function (): void {
    $invoice = specInvoice();
    unset($invoice['buyer']['idType'], $invoice['buyer']['idNumber']);

    expect(fn () => new InvoiceDto($invoice))
        ->toThrow(InvalidInvoiceException::class, 'required for a COMPANY');
});

it('requires a country for a VAT identifier', function (): void {
    $invoice = specInvoice();
    unset($invoice['buyer']['idCountryCodeAlpha2']);

    expect(fn () => new InvoiceDto($invoice))
        ->toThrow(InvalidInvoiceException::class, 'idCountryCodeAlpha2');
});

it('accepts an individual buyer without an identifier', function (): void {
    $invoice = specInvoice();
    $invoice['buyer']['type'] = 'PERSON';
    unset($invoice['buyer']['idType'], $invoice['buyer']['idNumber'], $invoice['buyer']['idCountryCodeAlpha2']);

    $dto = new InvoiceDto($invoice);

    expect($dto->buyer->type)->toBe(InvoiceBuyerType::PERSON)
        ->and($dto->buyer->idType)->toBeNull();
});

it('rejects a buyer missing a required field', function (string $field): void {
    $invoice = specInvoice();
    unset($invoice['buyer'][$field]);

    expect(fn () => new InvoiceDto($invoice))->toThrow(InvalidInvoiceException::class, "buyer.$field");
})->with([['email'], ['fullName'], ['street'], ['city'], ['postalCode'], ['countryCodeAlpha2']]);

it('rejects an invoice with no positions', function (): void {
    $invoice = specInvoice();
    $invoice['positions'] = [];

    expect(fn () => new InvoiceDto($invoice))
        ->toThrow(InvalidInvoiceException::class, 'at least one position');
});

it('rejects a negative quantity', function (): void {
    expect(fn () => new InvoiceDto(specInvoice(['positions' => [['quantity' => -1]]])))
        ->toThrow(InvalidInvoiceException::class, 'quantity');
});

it('hydrates an invoice on an api transaction', function (): void {
    $dto = new TransactionDto(['invoice' => specInvoice()]);

    expect($dto->invoice)->toBeInstanceOf(InvoiceDto::class)
        ->and($dto->invoice->buyer->idType)->toBe(InvoiceIdType::VAT_ID)
        ->and($dto->toArray()['invoice']['positions'][0]['taxStake'])->toBe('TAX_23');
});

it('carries the invoice on a paywall transaction as one signed field', function (): void {
    $dto = new PaywallTransactionDto([
        'amount' => 12300,
        'currency' => Currency::PLN,
        'orderId' => 'Split payment',
        'invoice' => specInvoice(),
    ]);

    $payload = $dto->toArray();

    expect($payload['invoice'])->toBeString();

    $decoded = json_decode((string) gzdecode((string) base64_decode($payload['invoice'])), true);
    expect($decoded['invoiceId'])->toBe('04/12/23');

    $signed = $payload;
    unset($signed['signature']);

    [$hash] = explode(';', (string) $payload['signature']);

    expect($hash)->toBe(
        hash('sha256', app(Utils::class)->buildQuery($signed).config('services.imoje.service_key'))
    );
});

it('requires taxAmount when invoiceId marks a Split Payment invoice', function (): void {
    $invoice = specInvoice();
    unset($invoice['positions'][0]['taxAmount']);

    expect(fn () => new InvoiceDto($invoice))
        ->toThrow(InvalidInvoiceException::class, 'taxAmount');
});

it('accepts an accounting invoice without invoiceId or taxAmount', function (): void {
    $invoice = specInvoice();
    unset($invoice['invoiceId'], $invoice['positions'][0]['taxAmount']);

    $dto = new InvoiceDto($invoice);

    expect($dto->invoiceId)->toBeNull()
        ->and($dto->positions[0]->taxAmount)->toBeNull()
        ->and($dto->toArray())->not->toHaveKey('invoiceId');
});
