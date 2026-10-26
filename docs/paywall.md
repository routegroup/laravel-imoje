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

## ING Księgowość (bookkeeping solution)

Not implemented — [#74](https://github.com/routegroup/laravel-imoje/issues/74).

## ING Lease Now

Not implemented — [#75](https://github.com/routegroup/laravel-imoje/issues/75).

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
