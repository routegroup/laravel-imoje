# Paywall

Reference documentation: [ING Pay FRONT API](https://bump.sh/pgw/doc/ing-pay-paywall).

## How to make a transaction

You can easily utilize creating an transaction without exposing data in views.
It'll add `serviceId`, `merchantId` and `signature` by default.

`createTransaction()` returns a `RedirectResponse`: the parameters travel in the
query string of a GET. The specification allows POST or GET, and a form POST is
what its own examples show, so prefer a form of your own for payloads carrying
personal data or a cart.

**Please be aware that it's only an example!**

```php
use Routegroup\Imoje\Payment\Lib\Paywall;
use Routegroup\Imoje\Payment\Types\Currency;
use Routegroup\Imoje\Payment\DTO\Paywall\TransactionDto;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

public function postPayment(Paywall $paywall): RedirectResponse 
{
    $dto = new TransactionDto([
        'amount' => 100 * 100, // 100zł
        'currency' => Currency::PLN,
        'orderId' => (string) Str::uuid(),
        'customerFirstName' => 'Firstname',
        'customerLastName' => 'Lastname',
        'customerEmail' => 'address@email.com',
    ]);
    return $paywall->createTransaction($dto);
}
```

## Notification

Please refer to [docs of notifications](notifications.md).

## Refund

Refund is only possible through API interface. Please refer to [API docs](api.md).

## OneClick payment

The package helps you quickly apply the widget form on your page.
In an example, I'll use an authorized user who is making a new transaction.

```php
use Routegroup\Imoje\Payment\Lib\Config;
use Routegroup\Imoje\Payment\DTO\Paywall\OneClickDto;
use Routegroup\Imoje\Payment\Types\Currency;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

public function getForm(Config $config)
{
    $auth = Auth::user();
    
    $dto = new OneClickDto([
        'amount' => 100 * 100, // 100zł
        'currency' => Currency::PLN,
        'orderId' => (string) Str::uuid(),
        'customerId' => $auth->id,
        'customerFirstName' => $auth->firstname,
        'customerLastName' => $auth->lastname,
        'customerEmail' => $auth->email,
    ]);
    
    return view('view-with-widget', [
        'widgetUrl' => $config->env->widgetUrl(),
        'dto' => $dto,
    ]);
}
```

## Deferred payments

Nothing special is needed. They are ordinary channels — `paypo`,
`imoje_twisto`, `blik_paylater`, `pragma_go` — so select them through
`visibleMethod` or `preselectMethodCode` like any other.

## Card widget

Partially supported: the URLs and most parameters exist, but the package does
not render the script tag — [#79](https://github.com/routegroup/laravel-imoje/issues/79).

## Language

`Paywall::createTransaction()` takes an optional `Lang`, which selects the
gateway's language segment:

```php
$paywall->createTransaction($dto, Lang::UK);
// https://paywall.pay.ing.pl/uk/payment
```

Omit it and the gateway picks for itself. The same enum types `customer.locale`
on the API side.

`Lang::UA` is deprecated. ING spells Ukrainian `uk`: the gateway does not serve
`/ua/`, and the API rejects `ua` in `customer.locale` with a 422. It still
resolves to `Lang::UK` so existing code keeps working — use `Lang::UK`.

## Multipayout

Supported. Pass `multipayout` as a flat list on the transaction DTO; it is
validated the same way as on the API side and signed as nested parameters —
`multipayout[0][ban]` and so on — which is what the gateway expects.
## Instalments

Instalments appear among the other methods by default. If you pass
`visibleMethod`, include `imoje_installments`.

The calculator widget is rendered into an element with id
`imoje-installments__wrapper` by the script at `Environment::installmentsUrl()`.
`InstallmentsCalculatorDto` builds and signs its options:

```php
$options = new InstallmentsCalculatorDto(['amount' => 50000]);
// merchantId, serviceId, amount, currency and signature, ready for the widget
```

The amount must be between 30 000 and 5 000 000 (300 to 50 000 PLN), which the
DTO enforces. The widget posts a `message` event carrying `channel` and
`period`: `channel` is the `paymentMethodCode` to use and `period` goes under
`installment` when you create the transaction.

## Split Payment

Attach an `invoice` to the transaction. The package builds it, validates it and
encodes it the way the gateway expects — gzip at level 5, base64, carried as one
signed field.

```php
$dto = new TransactionDto([
    // ...
    'visibleMethod' => 'wt',
    'invoice' => [
        'invoiceId' => '04/12/23',
        'buyer' => [
            'type' => 'COMPANY', 'idType' => 'VAT_ID', 'idNumber' => '5354235387',
            'email' => 'firma@example.com', 'fullName' => 'Nazwa firmy',
            'street' => 'Ulica', 'city' => 'Miasto', 'postalCode' => '12-345',
            'countryCodeAlpha2' => 'PL', 'idCountryCodeAlpha2' => 'PL',
        ],
        'positions' => [[
            'name' => 'Produkt', 'code' => 'produkt-01', 'quantity' => 1,
            'unit' => 'Sztuki', 'taxStake' => 'TAX_23',
            'grossAmount' => 12300, 'taxAmount' => 2300,
        ]],
    ],
]);
```

`invoiceId` sits beside `buyer` and `positions`, not inside `buyer`. A company
buyer needs `idType` and `idNumber`, and a `VAT_ID` needs
`idCountryCodeAlpha2`. `taxStake` takes either `TAX_23` or a bare `23`.
Violations raise `InvalidInvoiceException` naming the field.

The same `invoice` works on the API side, where it travels as a plain object
rather than encoded.

## ING Księgowość

The same `invoice` field carries a second, narrower variant. Leave out
`invoiceId` and the package switches to it: positions then carry no `taxAmount`,
and two further fields become available.

```php
'invoice' => [
    'buyer' => [
        'type' => 'PERSON', 'email' => 'jan.kowalski@example.com',
        'fullName' => 'Jan Kowalski', 'street' => 'Ulica', 'city' => 'Miasto',
        'postalCode' => '12-345', 'countryCodeAlpha2' => 'PL',
    ],
    'positions' => [[
        'name' => 'Produkt', 'code' => 'produkt-01', 'quantity' => 1,
        'unit' => 'Sztuki', 'taxStake' => 'TAX_23', 'grossAmount' => 12300,
    ]],
    'issueInvoice' => true,
    'basisForVatExemption' => ['type' => 'TUTORING'],
],
```

`issueInvoice` asks ING to issue the invoice once the payment settles.
`basisForVatExemption` takes a `VatExemptionType` and, for `OTHER`, a non-empty
`text`; omit it and `InvalidInvoiceException` names the field. Note ING's own
spelling of `GUARANTIEES` and `DENTAL_TECHNICAN_SERVICES`.

On the API side `Api::createPayment()` additionally takes `isKsef` to send the
invoice to the national e-invoicing system:

```php
$api->createPayment(new PaymentDto([
    // ...
    'isKsef' => true,
    'invoice' => $invoice,
]));
```

`POST /payment` currently rejects **every** value of `basisForVatExemption`,
including the ones its own error messages ask for. One branch of the gateway's
invoice schema declares the field with an empty list of permitted types, so no
object, string, number, array, boolean or null can satisfy it. Probed on the
sandbox in October 2026; the rest of the variant is accepted. Until ING fixes
the schema, send that one field through the paywall rather than the API.

## ING Lease Now

Attach a `cart` to the transaction. It is built, validated and encoded the same
way as `invoice` — gzip at level 5, base64, carried as one signed field — and
works on the API side too, where it travels as a plain object.

```php
$dto = new TransactionDto([
    // ...
    'visibleMethod' => 'lease',
    'cart' => [
        'items' => [[
            'id' => '1', 'categoryId' => 'category123', 'name' => 'Product name',
            'amount' => 500000, 'tax' => 115000, 'taxStake' => 23, 'quantity' => 1,
            'unit' => 'szt', 'url' => 'https://product.url', 'state' => 'NEW',
            'discount' => ['amount' => 100, 'tax' => 23],
        ]],
    ],
]);
```

`amount` is **net**, in the currency's smallest unit. `id`, `name`, `amount`,
`tax`, `taxStake`, `quantity`, `url` and `categoryId` are required; `unit`,
`state` and `discount` are not. `quantity` must be at least 1, `url` must parse,
and a `discount` needs both `amount` and `tax` or neither. Violations raise
`InvalidCartException` naming the field.

`taxStake` uses a vocabulary of its own, **not** the invoice's. The percentages
go as numbers — `23`, not `'23'` and not `TAX_23` — while `TAX_EXEMPT`,
`TAX_NOT_LIABLE`, `TAX_REVERSE_CHARGE` and `TAX_EXCLUDING` stay strings. You may
write any of the three spellings; `LeaseTaxStake` translates them to the one the
gateway takes, so `TAX_0` is sent as `0`.

`state` is `NEW` or `USED` — a string, despite the chapter typing it `integer`.

`visibleMethod` set to `lease` shows leasing alone on the gateway. Note that
`lease` is a `visibleMethod` value only: it is not a `paymentMethod`, even
though one PHP enum currently serves both.

A single product must be at least 1000 PLN net and the whole cart at least
5000 PLN net. Neither is checked locally, and the sandbox accepts smaller
amounts at this stage, so the limit surfaces later in the leasing flow.

`categoryId` is required here because both the chapter and the API schema say
so, though the sandbox currently accepts an item without it.
