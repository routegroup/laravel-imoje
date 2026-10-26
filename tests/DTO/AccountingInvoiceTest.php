<?php

namespace Routegroup\Imoje\Payment\Tests\DTO;

use Routegroup\Imoje\Payment\DTO\Api\PaymentDto;
use Routegroup\Imoje\Payment\DTO\Casts\InvoiceDto;
use Routegroup\Imoje\Payment\DTO\Casts\VatExemptionDto;
use Routegroup\Imoje\Payment\Exceptions\InvalidInvoiceException;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\Types\VatExemptionType;

/** The example from the ING Księgowość chapter: no invoiceId, no taxAmount. */
function accountingInvoice(array $overrides = []): array
{
    return array_replace_recursive([
        'buyer' => [
            'type' => 'PERSON',
            'email' => 'jan.kowalski@example.com',
            'fullName' => 'Jan Kowalski',
            'street' => 'Street',
            'city' => 'City',
            'postalCode' => '12-345',
            'countryCodeAlpha2' => 'PL',
        ],
        'positions' => [[
            'name' => 'Produkt',
            'code' => 'produkt-01',
            'quantity' => 1,
            'unit' => 'Sztuki',
            'taxStake' => 'TAX_23',
            'grossAmount' => 12300,
        ]],
    ], $overrides);
}

it('builds the accounting invoice from the chapter example', function (): void {
    $payload = (new InvoiceDto(accountingInvoice()))->toArray();

    expect(array_keys($payload))->toBe(['buyer', 'positions'])
        ->and($payload['positions'][0])->not->toHaveKey('taxAmount');
});

it('carries the automatic-dispatch flag as a boolean', function (): void {
    $payload = (new InvoiceDto(accountingInvoice(['issueInvoice' => 1])))->toArray();

    expect($payload['issueInvoice'])->toBeTrue();
});

it('types the exemption basis', function (): void {
    $invoice = new InvoiceDto(accountingInvoice([
        'basisForVatExemption' => ['type' => 'TUTORING'],
    ]));

    expect($invoice->basisForVatExemption)->toBeInstanceOf(VatExemptionDto::class)
        ->and($invoice->basisForVatExemption->type)->toBe(VatExemptionType::TUTORING)
        ->and($invoice->toArray()['basisForVatExemption'])->toBe(['type' => 'TUTORING']);
});

/** Verbatim from the gateway's own enum error, GUARANTIEES and TECHNICAN included. */
it('covers every exemption basis the gateway lists', function (): void {
    expect(array_column(VatExemptionType::cases(), 'value'))->toBe([
        'DENTAL_TECHNICAN_SERVICES', 'DOCTOR_DENTIST_SERVICES', 'PHYSIOTHERAPY_SERVICES',
        'NURSING_SERVICES', 'PSYCHOLOGICAL_SERVICES', 'MEDICAL_TRANSPORT_SERVICES',
        'CARE_SERVICES', 'TUTORING', 'TEACHING_FOREIGN_LANGUAGES', 'ARTISTS',
        'RENTING_PROPERTY', 'INSURANCE_SERVICES', 'CREDITS_AND_LOANS_SERVICES',
        'GUARANTIEES', 'SPECIAL_CONDITIONS_FOR_EXEMPTION', 'UE_TRANSACTIONS',
        'SUBJECTIVE_EXEMPTIONS', 'OTHER', 'OTHER_OBJECTIVE_EXEMPTIONS',
    ]);
});

it('demands a description for an unlisted exemption', function (): void {
    expect(fn () => new InvoiceDto(accountingInvoice([
        'basisForVatExemption' => ['type' => 'OTHER'],
    ])))->toThrow(InvalidInvoiceException::class, 'required when type is OTHER');
});

/** The gateway puts minLength 1 on text, so an empty one is as good as missing. */
it('demands a non-empty description for an unlisted exemption', function (): void {
    expect(fn () => new InvoiceDto(accountingInvoice([
        'basisForVatExemption' => ['type' => 'OTHER', 'text' => ''],
    ])))->toThrow(InvalidInvoiceException::class, 'required when type is OTHER');
});

it('accepts an unlisted exemption with a description', function (): void {
    $invoice = new InvoiceDto(accountingInvoice([
        'basisForVatExemption' => ['type' => 'OTHER', 'text' => 'Inna podstawa'],
    ]));

    expect($invoice->basisForVatExemption->text)->toBe('Inna podstawa');
});

it('rejects an exemption with no basis at all', function (): void {
    expect(fn () => new InvoiceDto(accountingInvoice(['basisForVatExemption' => ['text' => 'bez typu']])))
        ->toThrow(InvalidInvoiceException::class, 'basisForVatExemption.type');
});

it('carries the KSeF flag on a payment link', function (): void {
    $payload = (new PaymentDto([
        'amount' => 12300,
        'currency' => Currency::PLN,
        'orderId' => 'ksef-1',
        'isKsef' => 1,
        'invoice' => accountingInvoice(),
    ]))->toArray();

    expect($payload['isKsef'])->toBeTrue()
        ->and($payload['invoice'])->not->toHaveKey('invoiceId');
});
