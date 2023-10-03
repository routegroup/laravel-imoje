# Paywall

For more details, please follow the provider's docs.

- [PL](https://imojepaywall.docs.apiary.io/)
- [EN](https://imojepaywalleng.docs.apiary.io/)

## How to make a transaction

You can easily utilize creating an transaction without exposing data in views.
It'll add `serviceId`, `merchantId` and `signature` by default.

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
        'amount' => 100 * 100 // 100zł
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

## Multipayout

Not implemented.

## ING Księgowość (bookkeeping solution)

Not implemented.

## ING Lease Now

Not implemented.
