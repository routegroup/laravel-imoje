# Transakcje

Audyt zgodności `routegroup/laravel-imoje` ze specyfikacją ING Pay RESTful API v1.0.2
(OpenAPI 3.1, `ing-pay-api.json`) dla 4 endpointów transakcyjnych.

Repo: `/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a`
(ścieżki plików poniżej są względne wobec tego katalogu).

## Ustalenia wspólne dla wszystkich 4 endpointów

### U1. `[BŁĄD]` `Api::validateResponse` wycisza wszystkie kody błędów poza 422 i 500

`src/Lib/Api.php:236-249`:

```php
if ($response->ok()) { return; }          // ok() == status 200

match ($response->status()) {
    422 => throw new ApiErrorException($response, $request),
    500 => throw new ApiErrorException($response),
    default => null                        // <-- 400, 401, 403, 404, 406, 503, 504 przechodzą bez wyjątku
};
```

Specyfikacja (`ing-pay-api-topics/05-kody-odpowiedzi-http.md`) definiuje globalnie kody
`400, 401, 403, 404, 406, 422, 500, 503, 504`, a `POST /{merchantId}/transaction` ma w
`responses` jawnie zadeklarowane **`406`** (`components.responses.transaction-response-406`).

Skutek jest gorszy niż brak wyjątku. Prześledzony tor wykonania dla odpowiedzi 406 z body
`{"apiErrorResponse": {...}}`:

1. `validateResponse` → `default => null`, brak wyjątku (`src/Lib/Api.php:247`).
2. `new TransactionResponseDto($response)` → `ResponseDto::__construct` ustawia
   `$attributes = ['apiErrorResponse' => [...]]` (`src/DTO/Responses/ResponseDto.php:17`).
3. `TransactionResponseDto` **nie** ustawia `$allowNull`, więc dziedziczy `false` z
   `BaseDto` (`src/DTO/BaseDto.php:16`). `castAttributes` dla każdego klucza z `casts`
   wymusza wartość (`src/DTO/BaseDto.php:26-28`), a `castAttribute` dla castu będącego
   `BaseDto` robi `new $castType($value ?? [])` (`src/DTO/BaseDto.php:43-47`).
4. Wynik: `$response->transaction` to **pusty** `Casts\TransactionDto` (`->id === null`),
   a `$response->action` to `ActionDto` z `url === ''`, `method === ''`.

Czyli wywołujący dostaje poprawnie wyglądający obiekt odpowiedzi zamiast błędu — transakcja
„istnieje” z pustym `id`. Dotyczy to też `404` (nieznany `transactionId` w GET/capture/void)
oraz `401`/`403` (zły token). Rekomendacja: traktować każdy status != 200 jako błąd.

### U2. `[ROZBIEŻNOŚĆ]` `ApiErrorException` zawsze ustawia kod 422

`src/Exceptions/ApiErrorException.php:33` — `parent::__construct($this->dto->message, 422)`,
także gdy rzeczywisty status to 500. Kod HTTP jest dostępny przez `$e->response->status()`,
ale `getCode()` wprowadza w błąd.

### U3. `[ROZBIEŻNOŚĆ]` brak jakiejkolwiek walidacji żądania po stronie biblioteki

`src/Lib/Validator.php` obsługuje wyłącznie notyfikacje (`fromNotification`, linia 26) —
nie jest wywoływany z `src/Lib/Api.php` w żadnym miejscu (sprawdzone: brak referencji
do `Validator` w `src/` poza samym plikiem). Żadne `required`, `maxLength`, `pattern`,
`minimum`/`maximum` ze specyfikacji nie są egzekwowane lokalnie. Każde naruszenie wychodzi
dopiero jako 422 z API. To świadoma decyzja projektowa (DTO = worek `Fluent`), ale warto
ją odnotować, bo punkty niżej o `maxLength`/`pattern` są jej konsekwencją.

### U4. `[INFO]` adresy URL i metody HTTP są zgodne ze specyfikacją

`Environment::apiUrl()` zwraca `https://api.pay.ing.pl/v1` / `https://api.sandbox.pay.ing.pl/v1`
(`src/Types/Environment.php:15-16`), a `Url` dokleja `merchant/{merchantId}/...`
(`src/Lib/Url.php:13-21`). Sklejony URL to `https://api.pay.ing.pl/v1/merchant/{merchantId}/transaction`,
co odpowiada serwerowi `https://api.pay.ing.pl/v1/merchant` + ścieżce `/{merchantId}/transaction`
ze specyfikacji. Potwierdzone testami `tests/Lib/UrlTest.php:13-19,45-51`.
Autoryzacja: `Http::withToken($this->config->apiKey)` (`src/Lib/Api.php:230`) = `Authorization: Bearer`,
zgodnie z `securitySchemes."Token autoryzacyjny"` (`type: http`, `scheme: bearer`).

---

## 1. `POST /{merchantId}/transaction` — Utworzenie transakcji

**Werdykt: ROZBIEŻNOŚCI** — URL, metoda i kluczowe pola są poprawne, ale 8 opcjonalnych pól
ze specyfikacji nie istnieje w kontrakcie DTO, `multipayout` jest wysyłany w złym miejscu,
a nadpisanie `serviceId`/`type` psuje payload.

Spec: `api-endpoints/post-merchantid-transaction.json`, body `components.schemas.transaction`.
Repo: `src/Lib/Api.php:41-49`, `src/Lib/Url.php:13-21`, `src/DTO/Api/TransactionDto.php`.

### 1.1 Metoda, URL, parametry ścieżki

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `$this->request()->post($url, ...)` — `src/Lib/Api.php:45` | ZGODNE |
| URL | `{server}/{merchantId}/transaction` | `.../v1/merchant/{merchantId}/transaction` — `src/Lib/Url.php:13-21` | ZGODNE |
| `merchantId` (path, required) | `string` | z `Config::$merchantId` — `src/Lib/Config.php:12` | ZGODNE |
| parametry query | brak | brak | ZGODNE |
| `requestBody` | `required: true` | `$dto->toArray()` | ZGODNE |

### 1.2 Request body — pole po polu

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `type` | string, **required**, enum `["sale"]` (tylko sale dla tego endpointu) | `TransactionType` cast, domyślnie `SALE` — `src/DTO/Api/TransactionDto.php:53,98` | ROZBIEŻNOŚĆ (enum szerszy: `refund`) |
| `serviceId` | string, **required**, maxLength 36, uuid v4 pattern | domyślnie z `Config` — `src/DTO/Api/TransactionDto.php:99`; brak castu, brak walidacji | BŁĄD przy nadpisaniu (1.4) |
| `amount` | **integer**, **required**, grosze | cast `'int'` — `:54` | ZGODNE |
| `currency` | string, **required**, enum 10 wartości, maxLength 3 | `Currency` cast — `:55` | NADMIAR (2 wartości) |
| `orderId` | string, **required**, maxLength 100, pattern | w shape `:72`, brak castu i walidacji | ROZBIEŻNOŚĆ |
| `paymentMethod` | string, **required**, enum 8 wartości | `PaymentMethod` cast — `:56` | NADMIAR (2 wartości) |
| `paymentMethodCode` | string, **required**, enum 48 wartości | `PaymentMethodCode` cast — `:57` | NADMIAR (7 wartości) |
| `successReturnUrl` | string, **required**, maxLength 300, uri | w shape `:75`, brak walidacji | ROZBIEŻNOŚĆ |
| `failureReturnUrl` | string, **required**, maxLength 300, uri | w shape `:76`, brak walidacji | ROZBIEŻNOŚĆ |
| `customer` | object, **required** (`customerTransaction`) | `CustomerDto` cast — `:58` | ZGODNE (szczegóły 1.3) |
| `notificationUrl` | string, opcjonalne, maxLength 300, uri | **brak** w docblocku, w array shape i w `casts` | BRAK |
| `title` | string, opcjonalne, maxLength 255, pattern | docblock `:34`, shape `:82` | ZGODNE |
| `additionalDescription` | string, opcjonalne, maxLength 255, pattern | **brak** | BRAK |
| `validTo` | `integer \| null`, opcjonalne; `null` = brak wygaśnięcia | docblock `:40`, shape `:88`; `null` jest wycinane przez `array_filter` | ROZBIEŻNOŚĆ (1.6) |
| `blikCode` | string, opcjonalne, pattern `^[0-9]{6}$` | **brak** | BRAK |
| `clientIp` | string, opcjonalne, ipv4/ipv6 (wymagane z `blikCode`) | **brak** | BRAK |
| `billing` | object, opcjonalne (`billing`, 9 pól z `taxId`) | `BillingDto` cast — `:59` | ZGODNE |
| `shipping` | object, opcjonalne (`shipping`, 8 pól **bez** `taxId`) | `BillingDto` cast — `:60` | ROZBIEŻNOŚĆ (1.7) |
| `card` | object, opcjonalne, 7 pól | `CardDto` cast — `:61`; brak `firstTransactionId` | BRAK pola (1.8) |
| `additionalData` | object, opcjonalne, 11 wymaganych podpól przy 3DS/GPay/ApplePay | docblock `:39`, shape `:87`; cast zakomentowany `@todo` — `:62` | ROZBIEŻNOŚĆ |
| `visibleMethod` | array of enum (8 wartości, w tym `lease`) | docblock `:38` jako `array`, shape `:83` jako `list<string>`; brak enuma | ROZBIEŻNOŚĆ |
| `data` → `data.multipayout` | object z wymaganym `multipayout` | w repo `multipayout` **na najwyższym poziomie**, brak klucza `data` | **BŁĄD** (1.5) |
| `invoice` | object, opcjonalne (ING Księgowość / Split Payment) | docblock `:42`, shape `:90`; cast zakomentowany `@todo` — `:64` | ROZBIEŻNOŚĆ |
| `profileType` | string, opcjonalne, enum `["FIRST","STANDARD"]` | **brak** | BRAK |
| `installment` | object `{period: integer}`, opcjonalne | **brak** | BRAK |
| `wallet` | object, opcjonalne (GPay/ApplePay 0-level) | **brak** | BRAK |

### 1.3 `customer` (`components.schemas.customerTransaction`) vs `src/DTO/Casts/CustomerDto.php`

| pole | spec | repo | status |
|---|---|---|---|
| `firstName` | string, **required**, maxLength 100, pattern | cast `'string'` — `:40` | ZGODNE |
| `lastName` | string, **required**, maxLength 100, pattern | cast `'string'` — `:41` | ZGODNE |
| `email` | string, **required**, maxLength 200, email | cast `'string'` — `:42` | ZGODNE |
| `phone` | string, maxLength 20, pattern `^[0-9\+\- ]+$` | w shape `:19`, passthrough | ZGODNE |
| `cid` | string, maxLength 36, pattern | w shape `:17`, passthrough | ZGODNE |
| `company` | string, maxLength 200, pattern | w shape `:18`, passthrough | ZGODNE |
| `locale` | enum 17 wartości (`locale`) | `Lang` cast — `:43` | **BŁĄD** (1.9) |

### 1.4 `[BŁĄD]` `array_merge_recursive` psuje `type` i `serviceId` przy jawnym podaniu

`src/DTO/Api/TransactionDto.php:97-100`:

```php
$attributes = array_merge_recursive([
    'type' => TransactionType::SALE,
    'serviceId' => $config->serviceId,
], $attributes);
```

`array_merge_recursive` dla **tych samych kluczy stringowych** scala wartości w tablicę
zamiast nadpisywać. Zweryfikowane uruchomieniem w PHP 8.4:

```
array_merge_recursive(['type'=>T::SALE,'serviceId'=>'cfg-id'], ['type'=>'sale','serviceId'=>'user-id'])
=> ['type' => ['name'=>'SALE','value'=>'sale', 0=>'sale'], 'serviceId' => ['cfg-id','user-id']]
```

Konsekwencje:

- `new TransactionDto(['serviceId' => $innySklep, ...])` — payload wyjdzie z
  `"serviceId": ["cfg-id","user-id"]`, API odpowie 422 (`transaction-response-422`:
  „zawiera niedopuszczalne parametry… wartości nie spełniają wymogów”). To blokuje
  konfigurację wielosklepową przy jednym `Config`.
- `new TransactionDto(['type' => TransactionType::SALE, ...])` — `castAttribute` dostaje
  tablicę i trafia do `TransactionType::from($value)` (`src/DTO/BaseDto.php:58`) →
  `TypeError`.

Bez nadpisania (najczęstszy przypadek) zachowanie jest poprawne, dlatego problem jest
„niewidoczny” w testach: `TransactionDtoFactory::definition()`
(`src/Factories/Api/TransactionDtoFactory.php:20-34`) nie ustawia ani `type`, ani `serviceId`.
Poprawka: `array_merge` / `+` zamiast `array_merge_recursive`.

### 1.5 `[BŁĄD]` `multipayout` wysyłany na najwyższym poziomie zamiast w `data`

Spec dla `transaction` (`components.schemas.transaction.properties.data`):

```json
"data": { "type": "object", "required": ["multipayout"],
          "properties": { "multipayout": { "$ref": "#/components/schemas/multipayout" } } }
```

Repo deklaruje `multipayout` bezpośrednio w ciele transakcji:
`src/DTO/Api/TransactionDto.php:41` (`@property-read array $multipayout`), `:89`
(`multipayout?: array<mixed>`), `:63` (zakomentowany cast).

Płaski `multipayout` jest poprawny **tylko** dla `POST /{merchantId}/payment`
(`components.schemas.payment.properties.multipayout`). Dla `/transaction` oraz
`/transaction/profile` spec wymaga opakowania w `data`. Wysłanie płaskiego pola skończy się
422 albo cichym pominięciem multiwypłaty — czyli przelewem środków w złe miejsce.
Klucz `data` nie występuje w repo w ogóle → dodatkowo `[BRAK]`.

### 1.6 `[ROZBIEŻNOŚĆ]` `array_filter` uniemożliwia wysłanie `validTo: null` i wycina wartości „falsy”

`src/DTO/BaseDto.php:30-32`:

```php
if ($this->allowNull) {
    $attributes = array_filter($attributes);   // bez callbacka = odrzuca wszystko falsy
}
```

`TransactionDto` ma `allowNull = true` (`src/DTO/Api/TransactionDto.php:50`). Zweryfikowane
w PHP 8.4: `array_filter(['validTo'=>null,'amount'=>0,'flag'=>false,'zero'=>'0','ok'=>1])`
zwraca wyłącznie `['ok'=>1]`.

Spec `components.schemas.validTo` mówi wprost: „Przekazanie parametru `validTo` = `NULL`
powoduje brak wygaśnięcia transakcji z pominięciem ustawień w parametrze *Aktywność płatności*”.
Biblioteka nie potrafi tego wyrazić — `null` jest wycinany, więc zawsze obowiązuje ustawienie
z Panelu lub 15 miesięcy. Dodatkowo `castAttribute` zwraca `null` dla każdej wartości `empty()`
jeszcze przed castem (`src/DTO/BaseDto.php:39-41`), co dotyczy też `amount = 0` i `false`.

### 1.7 `[ROZBIEŻNOŚĆ]` `shipping` używa `BillingDto`, które ma `taxId` spoza schematu `shipping`

`src/DTO/Api/TransactionDto.php:60` mapuje `shipping` na `BillingDto`.
`BillingDto` deklaruje `taxId` (`src/DTO/Casts/BillingDto.php:20,32`), a
`components.schemas.shipping` **nie ma** tego pola (ma je tylko `components.schemas.billing`).
Ponieważ `BaseDto` jest `Fluent` i przepuszcza nieznane klucze, `shipping.taxId` trafi do
payloadu → ryzyko 422 („zawiera niedopuszczalne parametry”).

### 1.8 `[BRAK]` `card.firstTransactionId`

`components.schemas.card` ma 7 właściwości; `src/DTO/Casts/CardDto.php:12-19` (shape) i
`:36-43` (casts) pokrywają 6 — brakuje `firstTransactionId` (`$ref: firstTransactionId`),
wymaganego przy obciążaniu karty w OneClick/recurring bez profilu kartowego.
Pole przejdzie przez `Fluent`, ale nie jest częścią kontraktu DTO (brak w docblocku, shape
i castach → PHPStan go nie widzi).

### 1.9 `[BŁĄD]` enum `Lang` nie odpowiada `components.schemas.locale`

Spec `locale` (17 wartości): `pl, en, cs, de, es, fr, it, lt, ru, sk, sl, uk, nl, hu, ro, bg, sv`.
`src/Types/Lang.php:7-22` (15 wartości) ma `ua` zamiast `uk` i nie ma `bg` ani `sv`.

- `Lang::UA` (`src/Types/Lang.php:18`) daje w payloadzie `"locale": "ua"` — wartość spoza
  enuma → 422.
- `new CustomerDto(['locale' => 'uk'])` / `'bg'` / `'sv'` rzuci `ValueError` z
  `Lang::from()` (`src/DTO/BaseDto.php:58`) — poprawnych wartości ze specyfikacji nie da się
  użyć.

### 1.10 `[NADMIAR]` wartości enumów obecne w repo, a nieobecne w specyfikacji

Porównanie wykonane programowo względem `api-components.json`:

| enum repo | nadmiarowe wartości | plik |
|---|---|---|
| `Currency` | `UAH`, `HRK` | `src/Types/Currency.php:14-15` |
| `PaymentMethod` | `lease`, `visa_mobile` | `src/Types/PaymentMethod.php:13,17` |
| `PaymentMethodCode` | `blik_oneclick`, `c2p`, `citi`, `envelo`, `ideabank`, `imoje_installments`, `tmobile` | `src/Types/PaymentMethodCode.php:12,19,21,24,27,28,54` |
| `TransactionStatus` | `submitted_for_settlement`, `refund` | `src/Types/TransactionStatus.php:12,17` |

Żadnej wartości ze specyfikacji **nie brakuje** w repo — nadmiar jest jednokierunkowy
(prawdopodobnie pozostałość po starszym imoje). Nie psuje odczytu odpowiedzi, ale pozwala
zbudować żądanie z wartością, której bramka odrzuci (np. `Currency::UAH`,
`PaymentMethod::LEASE`). Uwaga: `lease` jest poprawną wartością w `visibleMethodTransaction`,
ale nie w `paymentMethod`; `visa_mobile` jest poprawne jako `paymentMethodCode`, nie jako
`paymentMethod`.

### 1.11 `[ROZBIEŻNOŚĆ]` `type` dla tego endpointu dopuszcza tylko `sale`

`components.schemas.transaction.properties.type` to lokalny enum `["sale"]`, a nie
współdzielony `components.schemas.type` (`["sale","refund"]`). `TransactionType`
(`src/Types/TransactionType.php:9-10`) pozwala wysłać `refund`, czego ten endpoint nie przyjmie.
Dla odpowiedzi 200 i dla `GET` szerszy enum jest prawidłowy, więc to kwestia braku
rozdzielenia typu żądania od typu odpowiedzi.

### 1.12 Response 200 (`components.responses.transaction-response-200`)

Spec: `{ transaction: { id, type, status, source, created, modified, notificationUrl,
serviceId, amount, currency, title, orderId, paymentMethod, paymentMethodCode,
payment: {id, status}, action } }` — **`action` jest zagnieżdżony wewnątrz `transaction`**.

Repo: `src/DTO/Responses/TransactionResponseDto.php:22-25` (casty `transaction`, `action`)
+ `src/DTO/Casts/TransactionDto.php:52-70`.

| pole odpowiedzi | spec | repo | status |
|---|---|---|---|
| `transaction.id` | string uuid | cast `'string'` — `Casts/TransactionDto.php:53` | ZGODNE |
| `transaction.type` | enum `sale/refund` | `TransactionType` — `:54` | ZGODNE |
| `transaction.status` | enum 7 wartości | `TransactionStatus` — `:55` | ZGODNE (nadmiar 2) |
| `transaction.source` | enum `web/api` | `TransactionSource` — `:56` | ZGODNE |
| `transaction.created` | `integer \| null` | cast `'int'` — `:57` | ZGODNE |
| `transaction.modified` | `integer \| null` | cast `'int'` — `:58` | ZGODNE |
| `transaction.notificationUrl` | string | docblock `:24`, passthrough | ZGODNE |
| `transaction.serviceId` | string uuid | cast `'string'` — `:59` | ZGODNE |
| `transaction.amount` | **integer** | cast `'int'` — `:60` | ZGODNE |
| `transaction.currency` | enum | `Currency` — `:61` | ZGODNE |
| `transaction.title` | string | docblock `:28`, passthrough | ZGODNE |
| `transaction.orderId` | string | cast `'string'` — `:62` | ZGODNE |
| `transaction.paymentMethod` | enum | `PaymentMethod` — `:63` | ZGODNE |
| `transaction.paymentMethodCode` | enum | `PaymentMethodCode` — `:64` | ZGODNE |
| `transaction.payment.id` | string uuid | `PaymentSummaryDto` — `:65`, `Casts/PaymentSummaryDto.php:17` | ZGODNE |
| `transaction.payment.status` | enum | `PaymentSummaryDto` — `Casts/PaymentSummaryDto.php:18` | ZGODNE |
| `transaction.action` | obiekt `action` (7 pól) | `ActionDto` — `:69` | ZGODNE (szczegóły 1.13) |
| `action` (top-level) | **nie występuje** w tej odpowiedzi | cast `ActionDto` — `TransactionResponseDto.php:24` | NADMIAR (patrz 2.3) |

`[INFO]` `amount` jest w specyfikacji `integer`, a repo i tak robi `(int)` (`src/DTO/BaseDto.php:62`),
więc ewentualny string `"10000"` też zadziała. Odwrotnie natomiast: `Casts\TransactionDto`
ma `allowNull = true` (`:50`), więc `amount` równy `0` zostanie zamieniony na `null` i wycięty
(`src/DTO/BaseDto.php:39-41,30-32`). To samo dotyczy `paidAmount: 0` (`:67`) oraz
`fee.merchant` / `fee.surcharge` równych `0` (`src/DTO/Casts/FeeDto.php:15-20`) — zero prowizji
będzie nieodróżnialne od braku pola.

### 1.13 `action` (`components.schemas.action`) vs `src/DTO/Casts/ActionDto.php`

| pole | spec | repo | status |
|---|---|---|---|
| `type` | enum `redirect/transfer` | `ActionType` cast — `:28`; `ActionType` ma dokładnie te 2 wartości (`src/Types/ActionType.php:7-8`) | ZGODNE |
| `url` | string | cast `'string'` — `:29` | ZGODNE |
| `method` | enum `GET/POST` | cast `'string'`, brak enuma — `:30` | ROZBIEŻNOŚĆ |
| `contentType` | string | cast `'string'` — `:31` | ZGODNE |
| `contentBodyRaw` | string | cast `'string'` — `:32` | ZGODNE |
| `ban` | string (nr rachunku dla `transfer`) | cast `'string'` — `:33` | ZGODNE |
| `title` | string (tytuł przelewu dla `transfer`) | docblock `:19`, brak castu | ZGODNE (passthrough) |

`[ROZBIEŻNOŚĆ]` `ActionDto` nie ustawia `$allowNull`, więc dziedziczy `false`
(`src/DTO/BaseDto.php:16`). Każde pole z `casts` jest wymuszane nawet gdy go nie ma w
odpowiedzi — `ban` i `contentBodyRaw` dla akcji typu `redirect` wyjdą jako `''`, a nie `null`.
Dla `type` jest wyjątek (`src/DTO/BaseDto.php:54-56` zwraca `null`), co pokrywa test
`tests/Lib/ApiSpecTest.php:99-112`.

### 1.14 Kody błędów 422 / 406 / 500

| kod | spec | repo | status |
|---|---|---|---|
| `422` | `transaction-response-422`, body `{apiErrorResponse:{code,message,instance,errors}}` | `ApiErrorException` + `ApiErrorResponseDto` (`src/Lib/Api.php:245`) | ZGODNE |
| `406` | `transaction-response-406`, ten sam kształt body | **brak obsługi** — `default => null` (`src/Lib/Api.php:247`) | **BŁĄD** (U1) |
| `500` | `transaction-response-500`, ten sam kształt body | `ApiErrorException` bez `$request` (`src/Lib/Api.php:246`) | ZGODNE |

`ApiErrorResponseDto` (`src/DTO/Responses/ApiErrorResponseDto.php:17-21`) pokrywa wszystkie
4 pola `apiErrorResponse` (`code`, `message`, `instance`, `errors`), a `ApiErrorException`
poprawnie rozpakowuje kopertę `apiErrorResponse`
(`src/Exceptions/ApiErrorException.php:25-27`).

`[INFO]` 406 jest wg `ing-pay-api-topics/05-kody-odpowiedzi-http.md` oczekiwanym, rutynowym
błędem (nieaktywny kanał płatności, globalny limit po serii 500) — nie jest to rzadki
przypadek brzegowy.

### 1.15 Testy

`[ROZBIEŻNOŚĆ]` `tests/Lib/ApiTest.php:43-49` testuje tylko to, że zwrócony obiekt jest
instancją `TransactionResponseDto`. Mock odpowiedzi pochodzi z
`src/Factories/Responses/TransactionResponseDtoFactory.php:18-21`, który umieszcza `action`
**na najwyższym poziomie**, obok `transaction`. Specyfikacja dla `POST /transaction`
(`transaction-response-200`) zagnieżdża `action` **wewnątrz** `transaction`, więc rzeczywisty
kształt odpowiedzi POST nie jest przez testy w ogóle pokrywany. Fikstura odpowiada natomiast
kształtowi z `GET` (`get-transaction-response-200`).

`[ROZBIEŻNOŚĆ]` `src/Factories/Casts/CardDtoFactory.php:23` generuje
`'year' => $expirationDate->format('Y')` — 4 cyfry, a `components.schemas.card.year` ma
`maxLength: 2`. Fikstura produkuje dane, których API by nie przyjęło.

`[BŁĄD]` `src/Factories/Casts/ActionDtoFactory.php:29` — stan `wireTransfer()` ustawia klucz
`iban`, podczas gdy w `components.schemas.action` pole nazywa się `ban`. Dotyczy wyłącznie
fikstur testowych, nie kodu produkcyjnego.

---

## 2. `GET /{merchantId}/transaction/{transactionId}` — Pobranie danych transakcji

**Werdykt: ROZBIEŻNOŚCI** — wywołanie jest poprawne, ale trzy obiekty z odpowiedzi
(`refunds`, `payerData`, `payerCardData`) nie są hydrowane, a 404 nie jest wykrywany.

Spec: `api-endpoints/get-merchantid-transaction-transactionid.json`,
odpowiedź `components.responses.get-transaction-response-200`.
Repo: `src/Lib/Api.php:51-59`, `src/Lib/Url.php:23-29`,
`src/DTO/Responses/GetTransactionResponseDto.php`.

### 2.1 Metoda, URL, parametry

| element | spec | repo | status |
|---|---|---|---|
| metoda | `GET` | `->get($url)` — `src/Lib/Api.php:55` | ZGODNE |
| URL | `{server}/{merchantId}/transaction/{transactionId}` | `createGetTransactionUrl` — `src/Lib/Url.php:23-29` | ZGODNE |
| `merchantId` (path, required) | string | z `Config` | ZGODNE |
| `transactionId` (path, required) | string uuid v4, maxLength 36 | `string $transactionId`, brak walidacji formatu | ROZBIEŻNOŚĆ |
| query / body | brak | brak | ZGODNE |

`[INFO]` `src/Lib/Url.php:23` — `createGetTransactionUrl($transactionId)` nie ma typu
parametru (pozostałe metody mają `string`). Brak `rawurlencode`, ale przy poprawnym UUID
to bez znaczenia.

### 2.2 Response 200 — pole po polu

| pole | spec (typ) | repo | status |
|---|---|---|---|
| `transaction.id` … `transaction.paymentMethodCode` | jak w 1.12 | `Casts/TransactionDto.php:53-64` | ZGODNE |
| `transaction.payment.{id,status}` | object | `PaymentSummaryDto` — `:65` | ZGODNE |
| `transaction.refunds[]` | array obiektów `{transaction:{id,type,status,source,created,modified,serviceId,amount,currency,title,orderId,paymentMethod,paymentMethodCode}}` | docblock `array $refunds` (`:34`), **brak castu** | ROZBIEŻNOŚĆ (2.4) |
| `transaction.blikTransactionId` | string | docblock `:35`, passthrough | ZGODNE |
| `transaction.statusCode` | string | docblock `:36`, passthrough | ZGODNE |
| `transaction.statusCodeDescription` | string | docblock `:37`, passthrough | ZGODNE |
| `transaction.fee.merchant` | integer | `FeeDto` — `:68`, `Casts/FeeDto.php:18` | ZGODNE (uwaga o `0` w 1.12) |
| `transaction.fee.surcharge` | integer | `FeeDto` — `Casts/FeeDto.php:19` | ZGODNE (j.w.) |
| `transaction.payerData.{name,account}` | object (`payerData`) | docblock `array\|null` (`:39`), **brak castu** | ROZBIEŻNOŚĆ (2.4) |
| `transaction.payerCardData.{brand,bin,last4,type,countryCodeAlpha2,countryCodeAlpha3,countryName,firstTransactionId}` | object (`payerCardData`) | docblock `array\|null` (`:40`), **brak castu** | ROZBIEŻNOŚĆ (2.4) |
| `transaction.paidAmount` | integer | cast `'int'` — `:67` | ZGODNE (uwaga o `0`) |
| `action` (top-level) | obiekt `action` | `ActionDto` — `GetTransactionResponseDto.php:18` | ZGODNE |
| `transaction.paymentProfile` | **nie występuje** w tej odpowiedzi | `PaymentProfileDto` — `:66` | INFO (2.5) |

### 2.3 `[INFO]` niespójność w samej specyfikacji co do położenia `action`

`transaction-response-200` (POST) umieszcza `action` **wewnątrz** `transaction`, a
`get-transaction-response-200` (GET) **obok** `transaction`. Repo obsługuje oba warianty
jednocześnie: `Casts\TransactionDto` ma cast `action` (`:69`), a oba DTO odpowiedzi mają
cast `action` na najwyższym poziomie (`TransactionResponseDto.php:24`,
`GetTransactionResponseDto.php:18`). To pragmatyczne i w praktyce odporne, ale oznacza,
że w każdym z dwóch przypadków jeden z castów jest nadmiarowy — i że pole
„`action` nieobecne” nigdy nie da `null`, tylko pusty `ActionDto` (patrz 2.6).

### 2.4 `[ROZBIEŻNOŚĆ]` `refunds`, `payerData`, `payerCardData` zostają surowymi tablicami

`src/DTO/Casts/TransactionDto.php:52-70` nie zawiera castów dla tych trzech pól, mimo że
istnieją odpowiednie DTO/typy:

- `refunds` — każdy element to `{transaction: {...}}` z pełnym zestawem pól transakcji; mógłby
  być mapowany na `Casts\TransactionDto`. Obecnie `$dto->transaction->refunds[0]['transaction']['status']`
  to surowy string, a nie `TransactionStatus`; `amount` to surowa wartość z JSON-a bez castu.
- `payerData` — `{name, account}`, brak dedykowanego DTO w repo.
- `payerCardData` — `{brand, bin, last4, type, countryCodeAlpha2, countryCodeAlpha3,
  countryName, firstTransactionId}`, brak dedykowanego DTO; `type` ma w specyfikacji enum
  `DEBIT/CREDIT`, dla którego w `src/Types/` nie ma odpowiednika.

Dane nie giną (przechodzą przez `Fluent`), ale nie są typowane ani castowane — to główna
luka pokrycia tego endpointu, zwłaszcza że „dane z banku płatnika” (`payerData`) są
wprost reklamowane w opisie endpointu jako jego kluczowa funkcja.

### 2.5 `[INFO]` `paymentProfile` w `Casts\TransactionDto` nie pochodzi z tych endpointów

`src/DTO/Casts/TransactionDto.php:66` castuje `paymentProfile` na `PaymentProfileDto`.
Sprawdzone w specyfikacji: `paymentProfile` występuje wyłącznie w odpowiedziach
`debit-profile-*`, `get-profile-by-*`, `delete-profile-*`, `deactivate-profile-*` —
w `transaction-response-200` ani `get-transaction-response-200` go nie ma. `Casts\TransactionDto`
jest jednak współdzielone z `ChargeProfileResponseDto`
(`src/DTO/Responses/ChargeProfileResponseDto.php:21`) i z notyfikacjami
(`src/DTO/Notifications/GenericNotificationDto.php:28`), gdzie `transaction.paymentProfile`
jest prawidłowe (`debit-profile-response-200`). Nie jest to więc nadmiar do usunięcia,
tylko efekt współdzielenia jednego DTO przez kilka endpointów.

### 2.6 `[ROZBIEŻNOŚĆ]` docblock `ActionDto|null $action` nigdy nie jest `null`

`src/DTO/Responses/GetTransactionResponseDto.php:12` deklaruje `@property-read ActionDto|null $action`,
ale klasa nie ustawia `$allowNull` (dziedziczy `false`), więc `castAttribute` zawsze zwraca
`new ActionDto([])` dla brakującego klucza (`src/DTO/BaseDto.php:43-47`). Kod konsumenta
sprawdzający `if ($r->action === null)` nigdy nie zadziała — poprawny warunek to
`empty($r->action->url)`.

### 2.7 `[BŁĄD]` brak obsługi 404 dla nieznanego `transactionId`

Specyfikacja deklaruje dla tego endpointu tylko `200`, ale `ing-pay-api-topics/05-kody-odpowiedzi-http.md`
definiuje `404` („Nieznany zasób”) jako kod globalny. Zgodnie z U1 nieznane `transactionId`
nie wywoła wyjątku — `getTransaction()` zwróci `GetTransactionResponseDto` z pustym
`transaction` (`->id === null`). Dla metody, której jedynym zadaniem jest odpytanie o status
płatności, to najbardziej prawdopodobny w praktyce scenariusz cichej awarii.

---

## 3. `POST /{merchantId}/transaction/{transactionId}/capture` — Odebranie transakcji

**Werdykt: ZGODNE w zakresie, jaki specyfikacja definiuje** (metoda, URL, parametry ścieżki,
brak body); specyfikacja nie opisuje odpowiedzi, więc mapowania pól nie da się zweryfikować.

Spec: `api-endpoints/post-merchantid-transaction-transactionid-capture.json` +
`ing-pay-api-topics/07-preautoryzacja-transakcji.md`.
Repo: `src/Lib/Api.php:132-139`, `src/Lib/Url.php:86-93`.

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `->post($url)` — `src/Lib/Api.php:135` | ZGODNE |
| URL | `{server}/{merchantId}/transaction/{transactionId}/capture` | `createCaptureUrl` — `src/Lib/Url.php:86-93` | ZGODNE |
| `merchantId` (path, required) | string | z `Config` | ZGODNE |
| `transactionId` (path, required) | string uuid v4, maxLength 36 | `string $transactionId` — `src/Lib/Api.php:132` | ZGODNE |
| query | brak | brak | ZGODNE |
| `requestBody` | **nie zdefiniowane** | wysyłane `[]` (patrz 3.1) | INFO |
| `responses` | **puste — brak jakiejkolwiek odpowiedzi w spec** | `TransactionResponseDto` | INFO (3.2) |

### 3.1 `[INFO]` do `capture` wysyłane jest puste ciało JSON `[]`

`src/Lib/Api.php:135` wywołuje `->post($url)` bez drugiego argumentu. W Laravelu
`PendingRequest::post(string $url, $data = [])` przekazuje `['json' => []]` do Guzzle
(`vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php:917-922`,
`parseHttpOptions` nie usuwa pustej tablicy), a `json_encode([])` daje `[]` — czyli pustą
tablicę JSON, nie pusty obiekt. Żądanie leci z `Content-Type: application/json` (`asJson()`,
`src/Lib/Api.php:228`) i body `[]`.

Specyfikacja nie definiuje `requestBody` dla tego endpointu, więc **nie da się ze specyfikacji
rozstrzygnąć**, czy bramka to zaakceptuje. Odnotowane jako obserwacja, nie jako błąd.

### 3.2 `[INFO]` specyfikacja nie definiuje odpowiedzi dla `capture`

Obiekt `operation` dla tego endpointu nie zawiera w ogóle klucza `responses` (zweryfikowane
programowo na `ing-pay-api.json`). Repo zwraca `TransactionResponseDto`
(`src/Lib/Api.php:138`), co jest rozsądnym założeniem, ale **nie można go potwierdzić ani
obalić** względem dostarczonej specyfikacji. `ing-pay-api-topics/07-preautoryzacja-transakcji.md`
opisuje tylko efekt biznesowy: po `capture` transakcja przyjmuje status `settled`.

`[INFO]` Status `authorized` (wejściowy dla preautoryzacji) i `settled` (wyjściowy)
istnieją w `src/Types/TransactionStatus.php:10,14`, więc hydracja statusu zadziała.

### 3.3 `[BŁĄD]` brak obsługi błędów (U1)

`src/Lib/Api.php:136` → `validateResponse` przepuszcza wszystko poza 422 i 500. Próba
`capture` na transakcji w złym statusie, na nieistniejącym `transactionId` albo przy
wyłączonej preautoryzacji (wg `07-preautoryzacja-transakcji.md` jest domyślnie wyłączona)
zwróci obiekt `TransactionResponseDto` z pustym `transaction` zamiast wyjątku. Przy operacji
finansowej typu „odbierz środki” cicha porażka jest kosztowna.

### 3.4 `[ROZBIEŻNOŚĆ]` test nie weryfikuje niczego poza typem zwracanego obiektu

`tests/Lib/ApiTest.php:106-112` mockuje odpowiedź fiksturą `TransactionResponseDto::factory()`
i sprawdza wyłącznie `toBeInstanceOf`. Nie jest sprawdzana ani metoda HTTP, ani URL, ani
ciało żądania, ani zachowanie przy statusie błędu.

---

## 4. `POST /{merchantId}/transaction/{transactionId}/void` — Odrzucenie transakcji

**Werdykt: ZGODNE w zakresie, jaki specyfikacja definiuje** — identycznie jak `capture`.

Spec: `api-endpoints/post-merchantid-transaction-transactionid-void.json` +
`ing-pay-api-topics/07-preautoryzacja-transakcji.md`.
Repo: `src/Lib/Api.php:141-148`, `src/Lib/Url.php:95-102`.

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `->post($url)` — `src/Lib/Api.php:144` | ZGODNE |
| URL | `{server}/{merchantId}/transaction/{transactionId}/void` | `createVoidUrl` — `src/Lib/Url.php:95-102` | ZGODNE |
| `merchantId` (path, required) | string | z `Config` | ZGODNE |
| `transactionId` (path, required) | string uuid v4, maxLength 36 | `string $transactionId` — `src/Lib/Api.php:141` | ZGODNE |
| query | brak | brak | ZGODNE |
| `requestBody` | **nie zdefiniowane** | wysyłane `[]` | INFO (jak 3.1) |
| `responses` | **puste — brak jakiejkolwiek odpowiedzi w spec** | `TransactionResponseDto` | INFO (jak 3.2) |

### 4.1 `[INFO]` status wynikowy `cancelled` jest obsłużony

Wg `ing-pay-api-topics/07-preautoryzacja-transakcji.md` po `void` transakcja przyjmuje status
`cancelled`. `TransactionStatus::CANCELLED` istnieje (`src/Types/TransactionStatus.php:16`),
więc hydracja zadziała.

### 4.2 `[BŁĄD]` brak obsługi błędów (U1)

`src/Lib/Api.php:145` — to samo co 3.3. Dodatkowo: ponieważ `capture` i `void` zwracają ten
sam typ i nie rzucają wyjątku przy błędzie, wywołujący nie ma żadnego sygnału, że odrzucenie
transakcji się nie powiodło.

### 4.3 `[ROZBIEŻNOŚĆ]` test jak przy `capture`

`tests/Lib/ApiTest.php:113-119` — wyłącznie `toBeInstanceOf`.

---

## Podsumowanie ilościowe

Liczone są **odrębne ustalenia**: zarówno te opisane w akapitach z etykietą, jak i te
wyrażone wyłącznie jako status w tabeli pól (np. brakujące pola żądania w 1.2). Ustalenie
powtórzone w kilku endpointach jako konsekwencja tej samej przyczyny (U1) liczone jest
osobno dla każdego endpointu, bo każde wymaga osobnej weryfikacji po poprawce.

| etykieta | liczba |
|---|---|
| `[BŁĄD]` | 8 |
| `[BRAK]` | 9 |
| `[ROZBIEŻNOŚĆ]` | 20 |
| `[NADMIAR]` | 5 |
| `[INFO]` | 10 |

### `[BŁĄD]` (8)
1. U1 — `validateResponse` wycisza 400/401/403/404/406/503/504 i zwraca pusty DTO (`src/Lib/Api.php:236-249`).
2. 1.4 — `array_merge_recursive` psuje nadpisanie `serviceId` (tablica w payloadzie) i `type` (`TypeError`) (`src/DTO/Api/TransactionDto.php:97-100`).
3. 1.5 — `multipayout` na najwyższym poziomie zamiast `data.multipayout` (`src/DTO/Api/TransactionDto.php:41,89`).
4. 1.9 — `Lang::UA = 'ua'` zamiast `uk`, brak `bg`/`sv` (`src/Types/Lang.php:18`).
5. 1.15 — `ActionDtoFactory::wireTransfer()` ustawia `iban` zamiast `ban` (`src/Factories/Casts/ActionDtoFactory.php:29`).
6. 2.7 — 404 przy `getTransaction` nie jest wykrywany (konsekwencja U1).
7. 3.3 — `captureTransaction` nie sygnalizuje błędu (konsekwencja U1).
8. 4.2 — `voidTransaction` nie sygnalizuje błędu (konsekwencja U1).

### `[BRAK]` (9)
`notificationUrl`, `additionalDescription`, `blikCode`, `clientIp`, `profileType`,
`installment`, `wallet`, `data` (wrapper multipayout) — wszystkie w
`src/DTO/Api/TransactionDto.php`; oraz `card.firstTransactionId` w `src/DTO/Casts/CardDto.php`.
Wszystkie przechodzą przez `Fluent`, ale żadne nie jest częścią kontraktu DTO
(brak w docblocku, w `@param array{...}` shape i w `casts`), więc PHPStan ich nie widzi,
a IDE nie podpowie.

### `[NADMIAR]` (5)
1. `Currency`: `UAH`, `HRK` (`src/Types/Currency.php:14-15`).
2. `PaymentMethod`: `lease`, `visa_mobile` (`src/Types/PaymentMethod.php:13,17`).
3. `PaymentMethodCode`: `blik_oneclick`, `c2p`, `citi`, `envelo`, `ideabank`,
   `imoje_installments`, `tmobile` (`src/Types/PaymentMethodCode.php:12,19,21,24,27,28,54`).
4. `TransactionStatus`: `submitted_for_settlement`, `refund` (`src/Types/TransactionStatus.php:12,17`).
5. Cast `action` na najwyższym poziomie w `TransactionResponseDto`
   (`src/DTO/Responses/TransactionResponseDto.php:24`) — `transaction-response-200` nie ma
   tam `action` (szczegóły w 2.3).

### Pięć najpoważniejszych rzeczy
1. **U1 — ciche połykanie błędów HTTP.** 406 jest wprost w `responses` dla `POST /transaction`
   i jest, wg dokumentacji ING, rutynowym błędem (nieaktywny kanał, globalny limit).
   Zamiast wyjątku kod dostaje `TransactionResponseDto` z pustym `transaction`. To samo
   dotyczy 404/401/403/503/504 we wszystkich czterech endpointach.
2. **1.5 — `multipayout` w złym miejscu payloadu.** Spec wymaga `data.multipayout` dla
   `/transaction`; płaski `multipayout` jest poprawny tylko dla `/payment`. Błąd w ścieżce
   rozdziału środków.
3. **1.4 — `array_merge_recursive`.** Nadpisanie `serviceId` daje w JSON-ie tablicę
   (gwarantowane 422), nadpisanie `type` rzuca `TypeError`. Blokuje obsługę wielu sklepów.
4. **2.4 — `payerData` / `payerCardData` / `refunds` bez castów.** Trzy obiekty z
   `get-transaction-response-200` zostają surowymi tablicami; `payerData` to funkcja wprost
   reklamowana w opisie endpointu.
5. **1.9 — enum `Lang` rozjechany ze specyfikacją.** `Lang::UA` generuje niedozwoloną
   wartość `"ua"` (spec: `uk`), a `uk`/`bg`/`sv` powodują `ValueError` przy budowie
   `CustomerDto` — poprawnych wartości nie da się użyć.
