# Linki płatności

Zakres: `POST /{merchantId}/payment`, `GET /{merchantId}/payment/{paymentId}`, `POST /{merchantId}/payment/cancel`.
Specyfikacja: ING Pay RESTful API v1.0.2 (`spec/api-endpoints/*.json`, `spec/api-components.json`).
Repo: `/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a`, gałąź `agent/ing-pay-api-audit-5dd10a`.

Uwaga wstępna: biblioteka nie waliduje requestów lokalnie (brak sprawdzania `required`, `maxLength`, `pattern`, `format`) — polega wyłącznie na walidacji po stronie API (odpowiedź 422). To świadoma decyzja architektoniczna, odnotowana raz jako `[INFO]` w sekcji wspólnej, nie powtarzana przy każdym polu.

---

## 1. `POST /{merchantId}/payment` — Utworzenie linku płatności

**Werdykt: ROZBIEŻNOŚCI** — metoda i URL są zgodne, ale DTO requestu nie pokrywa 7 pól ze specyfikacji, zawiera 1 pole spoza specyfikacji, a trzy mechanizmy (`array_merge_recursive` na `serviceId`, `array_filter` na `validTo`, enum `Lang`) powodują realne ryzyko błędów w działaniu.

### Metoda, URL, parametry ścieżki

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `Http::post()` — `src/Lib/Api.php:65` | ZGODNE |
| URL | `https://api.{sandbox.}pay.ing.pl/v1/merchant` + `/{merchantId}/payment` | `Url::createPaymentUrl()` — `src/Lib/Url.php:31-39` + `Environment::apiUrl()` — `src/Types/Environment.php:15-16` → `https://api.sandbox.pay.ing.pl/v1/merchant/$merchant_id$/payment` (test `tests/Lib/UrlTest.php:21-23`) | ZGODNE |
| `merchantId` (path, string, required) | z `Config::$merchantId` — `src/Lib/Url.php:36`, `src/Lib/Config.php:12` | ZGODNE |
| autoryzacja | HTTP Bearer | `Http::withToken($this->config->apiKey)` — `src/Lib/Api.php:230` | ZGODNE |
| nagłówki | `application/json` | `asJson()->acceptJson()` — `src/Lib/Api.php:228-229` | ZGODNE |

### Request body — `components.schemas.payment`

Repo: `src/DTO/Api/PaymentDto.php` (kształt tablicy: linie 47-65, casty: 40-44).

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `serviceId` | string, **required**, `format: uuid`, maxLength 36, pattern UUID v4 | shape l. 54; uzupełniany z `Config::$serviceId` przez `array_merge_recursive` l. 71-73; brak castu | ROZBIEŻNOŚĆ (patrz 1.1) |
| `amount` | integer, **required** (grosze) | cast `'int'` l. 41 | ZGODNE |
| `currency` | string, **required**, maxLength 3, enum 10 wartości (PLN, EUR, USD, GBP, CHF, SEK, HUF, CZK, BGN, RON) | cast `Currency::class` l. 42; enum ma 12 wartości | NADMIAR (1.5) |
| `orderId` | string, **required**, maxLength 100, pattern `^[A-Za-z0-9#_\-\.\\/ À-ˀ]+$` | shape l. 51, brak castu i walidacji | ZGODNE |
| `customer` | `customerPaymentLink`, **required** | cast `CustomerDto::class` l. 43 | ROZBIEŻNOŚĆ (1.3) |
| `title` | string, opcjonalne, maxLength 255, pattern | shape l. 56 | ZGODNE |
| `additionalDescription` | string, opcjonalne, maxLength 255, pattern | **brak** | BRAK (1.7) |
| `visibleMethod` | array of enum (`pbl`, `card`, `wallet`, `blik`, `imoje_paylater`, `wt`, `lease`, `imoje_installments`) | shape `list<string>` l. 57, bez typu wyliczeniowego | ROZBIEŻNOŚĆ (1.10) |
| `preselectMethodCode` | string, maxLength 20, enum 48 wartości | shape `string` l. 58, bez `PaymentMethodCode` | ROZBIEŻNOŚĆ (1.11) |
| `returnUrl` | string, `format: uri`, maxLength 300 | shape l. 59 | ZGODNE |
| `successReturnUrl` | string, `format: uri`, maxLength 300 | shape l. 60 | ZGODNE |
| `failureReturnUrl` | string, `format: uri`, maxLength 300 | shape l. 61 | ZGODNE |
| `notificationUrl` | string, `format: uri`, maxLength 300 | **brak** | BRAK (1.6) |
| `validTo` | `["integer","null"]`, min. 60 s; `null` = brak wygaśnięcia | shape `validTo?: int` l. 63, bez castu; `allowNull=true` l. 38 + `array_filter` usuwają `null` i `0` | BŁĄD (1.2) |
| `billing` | object, opcjonalne | **brak** | BRAK (1.8) |
| `shipping` | object, opcjonalne | **brak** | BRAK (1.8) |
| `cart` | object `{items: [...]}`, `items[]` wymaga `id`, `name`, `amount`, `tax`, `taxStake`, `quantity`, `url`, `categoryId` | shape `cart?: array<mixed>` l. 64, bez struktury | ROZBIEŻNOŚĆ (1.12) |
| `multipayout` | opcjonalne | **brak** | BRAK (1.9) |
| `invoice` | opcjonalne | **brak** | BRAK (1.9) |
| `isKsef` | boolean, opcjonalne | **brak** | BRAK (1.9) |
| `simp` | **nie istnieje w schemacie requestu** (tylko w odpowiedzi) | shape l. 62, `@property-read` l. 28 | NADMIAR (1.4) |

### Request body — `customer` (`components.schemas.customerPaymentLink`)

Repo: `src/DTO/Casts/CustomerDto.php` (shape l. 13-21, casty l. 39-44).

| pole | spec | repo | status |
|---|---|---|---|
| `firstName` | string, **required**, maxLength 100, pattern | cast `'string'` l. 40 | ZGODNE |
| `lastName` | string, **required**, maxLength 100, pattern | cast `'string'` l. 41 | ZGODNE |
| `email` | string, `format: email`, maxLength 200 | cast `'string'` l. 42 | ZGODNE |
| `phone` | string, maxLength 20, pattern `^[0-9\+\- ]+$` | shape l. 19, bez castu | ZGODNE |
| `cid` | string, maxLength 36, pattern | shape l. 17 | ZGODNE |
| `company` | string, maxLength 200, pattern | shape l. 18 | ZGODNE |
| `locale` | `$ref: locale` — enum 17 wartości: `pl, en, cs, de, es, fr, it, lt, ru, sk, sl, uk, nl, hu, ro, bg, sv` | cast `Lang::class` l. 43; `Lang` ma 15 wartości, brak `uk`, `bg`, `sv`, dodatkowo `ua` | BŁĄD (1.3) |

### Response 200 — `components.responses.payment-response-200`

Repo: `src/DTO/Responses/PaymentResponseDto.php` → cast `payment` na `src/DTO/Casts/PaymentDto.php` (casty l. 37-49).

| pole | spec | repo | status |
|---|---|---|---|
| `payment.id` | `paymentId` (string uuid) | `'string'` l. 43 | ZGODNE |
| `payment.url` | string | `'string'` l. 44 | ZGODNE |
| `payment.serviceId` | string uuid | `'string'` l. 38 | ZGODNE |
| `payment.orderId` | string | `'string'` l. 41 | ZGODNE |
| `payment.title` | string | bez castu, `@property-read` l. 20 | ZGODNE |
| `payment.simp` | string | bez castu, l. 21 | ZGODNE |
| `payment.amount` | integer | `'int'` l. 39 | ZGODNE |
| `payment.currency` | enum walut | `Currency::class` l. 40 | ZGODNE |
| `payment.notificationUrl` | string uri | bez castu, l. 22 | ZGODNE |
| `payment.returnUrl` | string uri | bez castu, l. 23 | ZGODNE |
| `payment.failureReturnUrl` | string uri | bez castu, l. 24 | ZGODNE |
| `payment.successReturnUrl` | string uri | bez castu, l. 25 | ZGODNE |
| `payment.customer` | `responseCustomer` | `CustomerDto::class` l. 42 | ROZBIEŻNOŚĆ (1.3) |
| `payment.isActive` | boolean | `'bool'` l. 45 | ZGODNE |
| `payment.validTo` | `["integer","null"]` | `'int'` l. 46, `allowNull=false` → `null` → `0` | BŁĄD (1.13) |
| `payment.created` | `["integer","null"]` | `'int'` l. 47 → `null` → `0` | ROZBIEŻNOŚĆ (1.14) |
| `payment.modified` | `["integer","null"]` | `'int'` l. 48 → `null` → `0` | ROZBIEŻNOŚĆ (1.14) |
| `additionalProperties` (dowolne nowe pola) | dozwolone | zachowywane przez `Fluent` (test `tests/Lib/ApiSpecTest.php:83-97`) | ZGODNE |

### Kody błędów

| kod | spec | repo | status |
|---|---|---|---|
| 422 | `payment-response-422` (`apiErrorResponse`: `code`, `message`, `instance`, `errors[]`) | `Api::validateResponse` l. 245 → `ApiErrorException`; mapowanie pól w `src/DTO/Responses/ApiErrorResponseDto.php:17-21` | ZGODNE |
| 500 | (globalnie, `ing-pay-api-topics/05`) | l. 246 → `ApiErrorException` | ZGODNE |
| 400, 401, 403, 404, 406, 503, 504 | globalnie wg `ing-pay-api-topics/05-kody-odpowiedzi-http.md` | `default => null` l. 247 — brak wyjątku | BŁĄD (1.15) |

### Ustalenia

**1.1 `[BŁĄD]` Jawne podanie `serviceId` psuje payload (`array_merge_recursive`)**
`src/DTO/Api/PaymentDto.php:71-73` scala domyślny `serviceId` z atrybutami przez `array_merge_recursive`. PHP przy kolizji kluczy tekstowych ze skalarami łączy wartości w tablicę, więc `new PaymentDto(['serviceId' => 'X', ...])` daje `serviceId => ['<config>', 'X']`, a `toArray()` wysyła tablicę JSON zamiast stringa — API odpowie 422 (`serviceId` wg `api-components.json` → `schemas.serviceId` to `type: string`). Poprawne byłoby `array_merge` lub `+`. Scenariusz jest realny dla sklepów wieloserwisowych. Brak testu pokrywającego nadpisanie `serviceId` (`grep serviceId tests/` trafia tylko w `ValidatorTest`/`ConfigTest`).

**1.2 `[BŁĄD]` Nie da się wysłać `validTo: null`, a `validTo` nie jest typowane jako nullable**
Specyfikacja (`api-components.json` → `schemas.validTo`) rozróżnia trzy przypadki: pole pominięte (obowiązuje ustawienie *Aktywność płatności* z panelu), `validTo: null` (**brak wygaśnięcia transakcji, z pominięciem ustawień panelu**) i wartość liczbowa. `PaymentDto` ma `allowNull = true` (l. 38), a `BaseDto::castAttributes` wykonuje `array_filter($attributes)` (`src/DTO/BaseDto.php:30-32`), które usuwa każdą wartość falsy — w tym `null` i `0`. W efekcie `new PaymentDto([..., 'validTo' => null])` jest nieodróżnialne od pominięcia pola, a semantyka „link nigdy nie wygasa” jest niedostępna. Dodatkowo kształt tablicy deklaruje `validTo?: int` (l. 63) zamiast `?int`, więc phpstan (poziom 4, `phpstan.neon.dist`) zgłosi błąd u konsumenta, który spróbuje przekazać `null`.

**1.3 `[BŁĄD]` Enum `Lang` nie pokrywa `locale` ze specyfikacji — `Lang::from()` rzuci `ValueError`**
`src/Types/Lang.php:7-22` definiuje 15 wartości. Wobec `api-components.json` → `schemas.locale` (17 wartości):
- brak: `uk` (ukraiński), `bg`, `sv`;
- nadmiar: `ua` (l. 18) — ING Pay używa `uk`, nie `ua`.

Skutki w obie strony: (a) `customer.locale = Lang::UA` w requeście wyśle `"ua"`, czyli wartość spoza enuma → 422; (b) odpowiedź API z `customer.locale: "uk"` (albo `bg`/`sv`) trafia do castu `Lang::class` w `src/DTO/Casts/CustomerDto.php:43`, a `BaseDto::castAttribute` l. 58 wywołuje `$castType::from($value)` bez `tryFrom` i bez przechwycenia — niezłapany `ValueError` wysypie `createPayment`, `getPayment` i `cancelPayment`. Dotyczy wszystkich trzech endpointów tej sekcji (`responseCustomer` ma to samo pole `locale`).

**1.4 `[NADMIAR]` `simp` w DTO requestu**
`src/DTO/Api/PaymentDto.php:28` i `:62` deklarują `simp`, którego `schemas.payment` nie zawiera (pole występuje wyłącznie w odpowiedziach: `payment-response-200`, `get-payment-response-200`, `cancel-payment-response-200`). `BaseDto` rozszerza `Fluent`, więc wartość zostanie przekazana do payloadu. Opis 422 w specyfikacji („zawiera niedopuszczalne parametry”) sugeruje, że API może taki request odrzucić.

**1.5 `[NADMIAR]` Enum `Currency` zawiera waluty spoza specyfikacji**
`src/Types/Currency.php:14-15` — `UAH` i `HRK` nie występują w `schemas.currency` (10 wartości). Wszystkie wartości ze specyfikacji są obecne, więc odpowiedzi API zawsze się zdeserializują; ryzyko dotyczy tylko wysłania `Currency::UAH`/`Currency::HRK` w requeście → 422.

**1.6 `[BRAK]` `notificationUrl`**
`schemas.payment.properties.notificationUrl` (dynamiczny adres notyfikacji dla pojedynczej transakcji) nie ma odpowiednika w `src/DTO/Api/PaymentDto.php:17-31` ani w kształcie l. 47-65. `Api\ChargeProfileDto.php:20,48` i `Api\TransactionDto.php` to pole obsługują, więc jest to luka specyficzna dla linków płatności. Pole jest natomiast odczytywane z odpowiedzi (`src/DTO/Casts/PaymentDto.php:22`).

**1.7 `[BRAK]` `additionalDescription`**
`schemas.additionalDescription` (string, maxLength 255) — brak w `src/DTO/Api/PaymentDto.php`.

**1.8 `[BRAK]` `billing` i `shipping`**
`schemas.payment.properties.billing` / `.shipping` — brak w `src/DTO/Api/PaymentDto.php`. Repo ma gotowy `src/DTO/Casts/BillingDto.php`, używany przez `src/DTO/Api/TransactionDto.php:59-60`, ale nie podpięty do linków płatności.

**1.9 `[BRAK]` `multipayout`, `invoice`, `isKsef`**
`schemas.payment.properties.multipayout`, `.invoice`, `.isKsef` — brak w `src/DTO/Api/PaymentDto.php`. W `src/DTO/Api/TransactionDto.php:63-64` istnieją komentarze `@todo` dla `multipayout` i `invoice`; dla linków płatności nie ma nawet tego.

**1.10 `[ROZBIEŻNOŚĆ]` `visibleMethod` bez typu wyliczeniowego**
`src/DTO/Api/PaymentDto.php:57` deklaruje `list<string>`, podczas gdy `schemas.visibleMethod.items.enum` ogranicza wartości do ośmiu (`pbl`, `card`, `wallet`, `blik`, `imoje_paylater`, `wt`, `lease`, `imoje_installments`). Repo ma `src/Types/PaymentMethod.php`, który pokrywa wszystkie osiem, ale zawiera też `ing` i `visa_mobile` spoza tego enuma — nie nadaje się do bezpośredniego użycia bez zawężenia.

**1.11 `[ROZBIEŻNOŚĆ]` `preselectMethodCode` jako `string`**
`src/DTO/Api/PaymentDto.php:24` i `:58` typują pole jako `string`, mimo że `schemas.preselectMethodCode` ma enum 48 wartości i `maxLength: 20`. Istniejący `src/Types/PaymentMethodCode.php` pokrywa wszystkie 48 wartości ze specyfikacji i nie jest tu wykorzystany.

**1.12 `[ROZBIEŻNOŚĆ]` `cart` bez struktury**
`src/DTO/Api/PaymentDto.php:30` i `:64` deklarują `array`/`array<mixed>`. `schemas.cart` wymaga `items[]` z ośmioma polami obowiązkowymi (`id`, `name`, `amount`, `tax`, `taxStake`, `quantity`, `url`, `categoryId`), w tym enuma `taxStake` (`23, 22, 8, 7, 5, 3, 0, TAX_EXEMPT, TAX_NOT_LIABLE, TAX_REVERSE_CHARGE, TAX_EXCLUDING`) i enuma `state` (`NEW`, `USED`). Pole jest wymagane przy aktywnej usłudze ING Lease Now (`ing-pay-api-topics/12-ing-lease-now.md`), więc brak modelu to realna przeszkoda w użyciu.

**1.13 `[BŁĄD]` `payment.validTo` w odpowiedzi: `null` → `0`**
`src/DTO/Casts/PaymentDto.php:46` castuje `validTo` na `'int'`, a DTO nie ustawia `allowNull`, więc obowiązuje domyślne `false` (`src/DTO/BaseDto.php:16`). `BaseDto::castAttribute` l. 62 wykonuje `(int) null`, czyli `0`. Specyfikacja (`payment-response-200`, przykład: `"validTo": null`) traktuje `null` jako „brak wygaśnięcia”, a `0` to timestamp 1 stycznia 1970 — konsument sprawdzający `$dto->payment->validTo < time()` uzna każdy bezterminowy link za wygasły. Niespójność wewnętrzna: te same pole w `GetPaymentResponseDto` i `CancelPaymentResponseDto` nie ma castu i zachowuje `null`.

**1.14 `[ROZBIEŻNOŚĆ]` `created` / `modified`: `null` → `0`**
`src/DTO/Casts/PaymentDto.php:47-48` — ten sam mechanizm co 1.13. `schemas.created` i `schemas.modified` to `["integer","null"]`. Ryzyko mniejsze (API w praktyce zawsze podaje timestamp utworzenia), ale typ `@property-read int` (l. 28-29) nie odwzorowuje specyfikacji.

**1.15 `[BŁĄD]` `validateResponse` nie reaguje na kody inne niż 422 i 500**
`src/Lib/Api.php:236-249`: `$response->ok()` to wyłącznie status 200, a `match` obsługuje tylko 422 i 500 — `default => null` (l. 247) oznacza, że 400, 401, 403, 404, 406, 503 i 504 przechodzą dalej bez wyjątku. `ing-pay-api-topics/05-kody-odpowiedzi-http.md` wymienia wszystkie te kody jako możliwe dla każdego endpointu, a 401 (zły token autoryzacyjny) i 403 są szczególnie prawdopodobne przy błędnej konfiguracji. Konsument otrzymuje wtedy `PaymentResponseDto` zbudowane z treści błędu, w którym `payment` to pusty `PaymentDto` (`id = ''`, `amount = 0`, `isActive = false`). Szczegółowy skutek dla pozostałych dwóch endpointów — ustalenia 2.1 i 3.1.

**1.16 `[INFO]` Brak lokalnej walidacji requestu**
Biblioteka nie sprawdza `required` (`serviceId`, `amount`, `currency`, `orderId`, `customer`, a wewnątrz `customer` — `firstName`, `lastName`), ani `maxLength`, `pattern`, `format: uri`/`email`, ani minimalnego czasu ważności `validTo` (60 s). `src/Lib/Validator.php` waliduje wyłącznie notyfikacje przychodzące (`fromNotification`, l. 26-89). Ze względu na `allowNull = true` + `array_filter` brakujące pola wymagane są po cichu usuwane z payloadu, więc błąd ujawnia się dopiero jako 422 z API.

**1.17 `[INFO]` `PaymentDtoFactory` nie pokrywa pól opcjonalnych**
`src/Factories/Api/PaymentDtoFactory.php:16-27` generuje tylko `amount`, `currency`, `orderId`, `customer`, `returnUrl`, `successReturnUrl`, `failureReturnUrl`. Żaden test nie przechodzi przez `title`, `visibleMethod`, `preselectMethodCode`, `validTo` ani `cart`, więc ustalenia 1.2 i 1.10-1.12 nie są wychwycone przez zestaw testów. Jedyny test `createPayment` (`tests/Lib/ApiTest.php:50-56`) sprawdza wyłącznie klasę zwróconego obiektu.

---

## 2. `GET /{merchantId}/payment/{paymentId}` — Pobranie danych linku płatności

**Werdykt: ROZBIEŻNOŚCI** — URL i mapowanie pól płaskich są zgodne (w tym poprawna obsługa `amount` przesyłanego jako string), ale tablica `transactions` nie jest modelowana, a brak obsługi kodów błędów powoduje ciche zwrócenie pustego DTO.

### Metoda, URL, parametry ścieżki

| element | spec | repo | status |
|---|---|---|---|
| metoda | `GET` | `Http::get()` — `src/Lib/Api.php:75` | ZGODNE |
| URL | `/{merchantId}/payment/{paymentId}` | `Url::createGetPaymentUrl()` — `src/Lib/Url.php:41-47`; test `tests/Lib/UrlTest.php:25-27` | ZGODNE |
| `merchantId` (path, string, required) | z `Config` — `src/Lib/Url.php:36` | ZGODNE |
| `paymentId` (path, **required**, `$ref: paymentId` → string, maxLength 36, `format: uuid`, pattern UUID v4) | `Api::getPayment(string $paymentId)` — `src/Lib/Api.php:71-79`; brak walidacji formatu i brak `rawurlencode` przed wklejeniem do URL | ROZBIEŻNOŚĆ (2.4) |
| parametry query | brak | brak | ZGODNE |

### Response 200 — `components.responses.get-payment-response-200`

Repo: `src/DTO/Responses/GetPaymentResponseDto.php` (casty l. 39-54, `@property-read` l. 11-36).

| pole | spec (typ, ograniczenia) | repo | status |
|---|---|---|---|
| `id` | `paymentId` (string uuid) | `'string'` l. 40 | ZGODNE |
| `url` | string | `'string'` l. 41 | ZGODNE |
| `serviceId` | string uuid | `'string'` l. 42 | ZGODNE |
| `orderId` | string | `'string'` l. 43 | ZGODNE |
| `title` | string | bez castu, `@property-read string\|null` l. 16 | ZGODNE |
| `simp` | string | bez castu, l. 17 | ZGODNE |
| `amount` | **`type: string`** (mimo że przykład pokazuje `100`) | `'int'` l. 44 → `(int) "10000"` | ZGODNE (2.2) |
| `currency` | **`$ref: status`** (błąd specyfikacji; przykład: `"PLN"`) | `Currency::class` l. 45 | ZGODNE (2.3) |
| `status` | `$ref: status` — enum `new, pending, settled, authorized, rejected, cancelled, error` | `TransactionStatus::class` l. 46 (nadzbiór: 9 wartości) | ZGODNE |
| `isActive` | boolean | `'bool'` l. 47 | ZGODNE |
| `validTo` | `["integer","null"]` | bez castu — `null` zachowane | ZGODNE |
| `created` | `["integer","null"]` | `'int'` l. 48 → `null` → `0` | ROZBIEŻNOŚĆ (2.6) |
| `modified` | `["integer","null"]` | `'int'` l. 49 → `null` → `0` | ROZBIEŻNOŚĆ (2.6) |
| `isGenerated` | boolean | `'bool'` l. 50 | ZGODNE |
| `isUsed` | boolean | `'bool'` l. 51 | ZGODNE |
| `usedAt` | integer | bez castu | ZGODNE |
| `isConfirmVisited` | boolean | `'bool'` l. 52 | ZGODNE |
| `confirmVisitedAt` | `["integer","null"]` | bez castu | ZGODNE |
| `returnUrl` | string uri | bez castu, l. 30 | ZGODNE |
| `failureReturnUrl` | string uri | bez castu, l. 31 | ZGODNE |
| `successReturnUrl` | string uri | bez castu, l. 32 | ZGODNE |
| `notificationUrl` | string uri | bez castu, l. 33 | ZGODNE |
| `customer` | `responseCustomer` (+ `additionalProperties`) | `CustomerDto::class` l. 53 | ROZBIEŻNOŚĆ (1.3) |
| `transactions[]` | array obiektów: `id`, `type`, `status`, `source`, `created`, `modified`, `notificationUrl`, `serviceId`, `amount`, `currency`, `title`, `orderId`, `paymentMethod`, `paymentMethodCode`, `payment{id,status}` | **brak castu** — surowa tablica asocjacyjna, `@property-read array $transactions` l. 35 | ROZBIEŻNOŚĆ (2.5) |
| `additionalProperties` | dozwolone | zachowywane przez `Fluent`; test `tests/Lib/ApiSpecTest.php:83-97` | ZGODNE |

### Kody błędów

| kod | spec | repo | status |
|---|---|---|---|
| 200 | jedyna zadeklarowana odpowiedź dla tego endpointu | — | ZGODNE |
| 400, 401, 403, 404, 406, 500, 503, 504 | globalnie (`ing-pay-api-topics/05`); dla GET szczególnie 404 („Nieznany zasób”) i 401 | tylko 500 → wyjątek; reszta → `default => null`, `src/Lib/Api.php:247` | BŁĄD (2.1) |

### Ustalenia

**2.1 `[BŁĄD]` Błąd HTTP zwraca puste DTO zamiast wyjątku**
`Api::getPayment` (`src/Lib/Api.php:71-79`) po `validateResponse` bezwarunkowo tworzy `GetPaymentResponseDto($response)`. Dla 404 (nieistniejący `paymentId`) lub 401 (zły token) ciało odpowiedzi to `{"apiErrorResponse": {...}}`, a `ResponseDto::__construct` (`src/DTO/Responses/ResponseDto.php:17`) przekazuje je do castów. Ponieważ `GetPaymentResponseDto` nie ustawia `allowNull`, każdy castowany klucz jest tworzony z wartości `null`: `id`/`url`/`serviceId`/`orderId` → `''`, `amount` → `0`, `currency` → `null`, `status` → `null`, `isActive`/`isGenerated`/`isUsed`/`isConfirmVisited` → `false`, `created`/`modified` → `0`, `customer` → pusty `CustomerDto`. Konsument dostaje obiekt wyglądający jak poprawna odpowiedź, bez żadnego sygnału błędu. `ing-pay-api-topics/05-kody-odpowiedzi-http.md` wprost opisuje 404 i 401 jako przypadki do obsłużenia.

**2.2 `[INFO]` `amount` jako string jest poprawnie konwertowany**
`get-payment-response-200` deklaruje `amount` jako `type: string` (w przeciwieństwie do `POST /payment`, gdzie jest to `$ref: amount` → `integer`). Cast `'int'` w `src/DTO/Responses/GetPaymentResponseDto.php:44` obsługuje obie formy; zachowanie jest pokryte testem `tests/Lib/ApiSpecTest.php:40-52`.

**2.3 `[INFO]` Błąd w specyfikacji: `currency` wskazuje na `$ref: status`**
W `get-payment-response-200` (i analogicznie w `cancel-payment-response-200`) pole `currency` ma `$ref: "#/components/schemas/status"`, co opisywałoby je enumem statusów. Przykład w tej samej odpowiedzi podaje `"currency": "PLN"`, więc jest to pomyłka po stronie ING. Repo castuje na `Currency::class` — zgodnie z faktycznym zachowaniem API, nie z literalnym schematem. Nie wymaga zmiany w kodzie; warto odnotować przy ewentualnym generowaniu DTO ze specyfikacji.

**2.4 `[INFO]` Brak walidacji i enkodowania `paymentId`**
`Api::getPayment` przyjmuje dowolny `string` i `Url::createGetPaymentUrl` (`src/Lib/Url.php:41-47`) wkleja go do ścieżki przez `implode('/')` bez `rawurlencode`. Specyfikacja wymaga UUID v4 (`schemas.paymentId`: maxLength 36, pattern). Wartość ze znakiem `/` lub `?` zmieni trafiony endpoint; ryzyko niskie (identyfikatory pochodzą z API), ale walidacja formatu odfiltrowałaby żądanie przed wyjściem na sieć.

**2.5 `[ROZBIEŻNOŚĆ]` `transactions[]` nie jest modelowane**
`src/DTO/Responses/GetPaymentResponseDto.php:39-54` nie zawiera castu dla `transactions`, więc konsument dostaje surowe tablice asocjacyjne zamiast DTO — bez enumów `TransactionType`, `TransactionStatus`, `TransactionSource`, `PaymentMethod`, `PaymentMethodCode` i bez zagnieżdżonego `payment{id,status}`. Repo ma komplet gotowych klas: `src/DTO/Casts/TransactionDto.php` (casty l. 52-70) pokrywa wszystkie 14 pól elementu `transactions[]`, a `src/DTO/Casts/PaymentSummaryDto.php:16-19` odpowiada zagnieżdżonemu `payment{id,status}`. To jedyne pole odpowiedzi, dla którego biblioteka nie dostarcza typowanego dostępu, mimo że nazwy pasują 1:1.

**2.6 `[ROZBIEŻNOŚĆ]` `created` / `modified`: `null` → `0`**
`src/DTO/Responses/GetPaymentResponseDto.php:48-49` — jak w ustaleniu 1.14. Niespójne z sąsiednimi polami czasowymi tego samego DTO (`validTo`, `usedAt`, `confirmVisitedAt` nie mają castu i zachowują `null`).

**2.7 `[INFO]` Testy nie pokrywają pełnego kształtu odpowiedzi**
`tests/Lib/ApiSpecTest.php:13-19` buduje payload dla `getPayment` z `CancelPaymentResponseDto::factory()`, a `src/Factories/Responses/CancelPaymentResponseDtoFactory.php:17-45` nie generuje ani `transactions`, ani `notificationUrl`. Cztery testy `getPayment` (`ApiSpecTest.php:40-97`) sprawdzają więc tylko: `amount` jako string, brak `status`, brak `customer.email` i zachowanie nieudokumentowanych pól. Tablica `transactions` — najbardziej złożona część odpowiedzi — nie jest przechodzona przez żaden test. Dodatkowo `tests/Lib/ApiTest.php:78-84` fake'uje odpowiedź `getPayment` fabryką `CancelPaymentResponseDto`, co maskuje ewentualne rozjechanie się obu kształtów.

---

## 3. `POST /{merchantId}/payment/cancel` — Anulowanie linku płatności

**Werdykt: ROZBIEŻNOŚCI** — request ma komplet dwóch wymaganych pól, ale zadeklarowana w specyfikacji odpowiedź 400 („Link nie może być anulowany”) nie jest obsługiwana i kończy się cichym sukcesem; dodatkowo cast `serviceId` na `'string'` zamienia skutek błędu 1.1 w payload `serviceId: "Array"`.

### Metoda, URL, parametry ścieżki

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `Http::post()` — `src/Lib/Api.php:85` | ZGODNE |
| URL | `/{merchantId}/payment/cancel` | `Url::createCancelPaymentUrl()` — `src/Lib/Url.php:49-55`; test `tests/Lib/UrlTest.php:29-31` | ZGODNE |
| `merchantId` (path, string, required) | z `Config` | ZGODNE |
| parametry query | brak | brak | ZGODNE |

### Request body — `components.schemas.cancel-payment`

Repo: `src/DTO/Api/CancelPaymentDto.php` (casty l. 22-25, shape l. 28-34).

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `serviceId` | string, **required**, maxLength 36, `format: uuid`, pattern UUID v4 | cast `'string'` l. 23; uzupełniany z `Config` przez `array_merge_recursive` l. 39-41 | BŁĄD (3.2) |
| `paymentId` | string, **required**, maxLength 36, `format: uuid`, pattern UUID v4 | cast `'string'` l. 24; brak walidacji formatu | ZGODNE |
| — | brak innych pól w schemacie | brak nadmiarowych pól w DTO | ZGODNE |

### Response 200 — `components.responses.cancel-payment-response-200`

Repo: `src/DTO/Responses/CancelPaymentResponseDto.php` (casty l. 45-60). Schemat odpowiedzi jest identyczny z `get-payment-response-200`, a casty w obu DTO są identyczne co do klucza i typu — ustalenia 2.5 i 2.6 obowiązują tak samo.

| pole | spec | repo | status |
|---|---|---|---|
| `id`, `url`, `serviceId`, `orderId` | string | `'string'` l. 46-49 | ZGODNE |
| `title`, `simp` | string | bez castu, l. 18-19 | ZGODNE |
| `amount` | `$ref: amount` → **integer** (inaczej niż w `get-payment-response-200`, gdzie `string`) | `'int'` l. 50 | ZGODNE |
| `currency` | `$ref: status` (ten sam błąd specyfikacji co 2.3; przykład: `"PLN"`) | `Currency::class` l. 51 | ZGODNE |
| `status` | enum statusów (przykład: `"cancelled"`) | `TransactionStatus::class` l. 52 | ROZBIEŻNOŚĆ (3.3) |
| `isActive` | boolean | `'bool'` l. 53 | ZGODNE |
| `validTo` | `["integer","null"]` | bez castu — `null` zachowane | ZGODNE |
| `created`, `modified` | `["integer","null"]` | `'int'` l. 54-55 → `null` → `0` | ROZBIEŻNOŚĆ (2.6) |
| `isGenerated`, `isUsed`, `isConfirmVisited` | boolean | `'bool'` l. 56-58 | ZGODNE |
| `usedAt` | integer | bez castu | ZGODNE |
| `confirmVisitedAt` | `["integer","null"]` | bez castu | ZGODNE |
| `returnUrl`, `failureReturnUrl`, `successReturnUrl`, `notificationUrl` | string uri | bez castu, l. 32-35 | ZGODNE |
| `customer` | `responseCustomer` | `CustomerDto::class` l. 59 | ROZBIEŻNOŚĆ (1.3) |
| `transactions[]` | array obiektów (14 pól + zagnieżdżone `payment{id,status}`) | **brak castu**, `@property-read array` l. 37 | ROZBIEŻNOŚĆ (2.5) |

### Kody błędów

| kod | spec | repo | status |
|---|---|---|---|
| 200 | `cancel-payment-response-200` | `CancelPaymentResponseDto` | ZGODNE |
| **400** | `cancel-payment-response-400` — **zadeklarowany wprost dla tego endpointu**: „Link nie może być anulowany”, ciało `{"apiErrorResponse": {"message": "Cannot cancel payment"}}` | `src/Lib/Api.php:244-248` — nieobsługiwany, `default => null` | BŁĄD (3.1) |
| 401, 403, 404, 500, 503, 504 | globalnie (`ing-pay-api-topics/05`) | tylko 500 | BŁĄD (1.15) |

### Ustalenia

**3.1 `[BŁĄD]` Zadeklarowana odpowiedź 400 jest ignorowana — nieudane anulowanie wygląda na udane**
`cancel-payment-response-400` to jedyna odpowiedź 4xx jawnie zadeklarowana w zakresie tego audytu poza 422, a `Api::cancelPayment` (`src/Lib/Api.php:81-89`) jej nie rozpoznaje: `validateResponse` obsługuje wyłącznie 422 i 500 (l. 244-248). Dla 400 metoda zwraca `CancelPaymentResponseDto` zbudowane z `{"apiErrorResponse": {"message": "Cannot cancel payment"}}`, w którym — przy `allowNull = false` — `id` to `''`, `status` to `null`, `isActive` to `false`, `amount` to `0`, a `customer` to pusty `CustomerDto`. Konsument, który po anulowaniu sprawdza `$response->isActive === false`, odczyta to jako potwierdzenie anulowania, podczas gdy link pozostał aktywny. To najpoważniejsze ustalenie w tym raporcie: błąd jest cichy, dotyczy operacji zmieniającej stan i jest wprost przewidziany przez specyfikację.

**3.2 `[BŁĄD]` Jawny `serviceId` daje payload `serviceId: "Array"`**
`src/DTO/Api/CancelPaymentDto.php:39-41` używa `array_merge_recursive` tak jak `PaymentDto` (ustalenie 1.1), ale tutaj `serviceId` ma cast `'string'` (l. 23). Przy kolizji kluczy `array_merge_recursive` tworzy tablicę `['<config>', 'X']`, a `BaseDto::castAttribute` l. 63 wykonuje `(string) ['<config>', 'X']` → ostrzeżenie „Array to string conversion” i literalna wartość `"Array"` w payloadzie. Request trafi do API z `serviceId: "Array"` i zakończy się 422 z komunikatem o niepoprawnym formacie — diagnostyka jest myląca, bo w kodzie wywołującym przekazano poprawny UUID.

**3.3 `[ROZBIEŻNOŚĆ]` Niespójna nullowalność `status` między dwoma DTO tej samej odpowiedzi**
`src/DTO/Responses/CancelPaymentResponseDto.php:22` deklaruje `@property-read TransactionStatus $status` (nienullowalne), a `src/DTO/Responses/GetPaymentResponseDto.php:20` dla tego samego pola i tego samego schematu odpowiedzi deklaruje `TransactionStatus|null`. Runtime jest taki sam w obu przypadkach — `BaseDto::castAttribute` l. 54-56 zwraca `null`, gdy pole nie przyszło — więc adnotacja w `CancelPaymentResponseDto` kłamie przed phpstanem (poziom 4) i przed konsumentem. Ujawnia się to zwłaszcza w scenariuszu z ustalenia 3.1, gdzie `status` jest `null`. Test `tests/Lib/ApiSpecTest.php:54-67` pokrywa brak `status`, ale tylko dla `getPayment`.

**3.4 `[INFO]` Brak walidacji formatu obu identyfikatorów**
`cancel-payment` wymaga, by `serviceId` i `paymentId` były UUID v4 (maxLength 36, pattern). `CancelPaymentDto` nie ustawia `allowNull`, więc pominięty `paymentId` zostanie zcastowany przez `(string) null` do `''` (`src/DTO/BaseDto.php:63`) i wysłany jako pusty string zamiast zostać wychwyconym lokalnie.

**3.5 `[INFO]` Brak testów ścieżek błędu**
`tests/Lib/ApiTest.php` (176 linii) zawiera wyłącznie przypadki pozytywne — `grep` po `ApiErrorException` w `tests/` trafia tylko w `tests/PlaygroundTest.php:5,30` (test integracyjny z realnym API). Żaden test nie fake'uje odpowiedzi 400, 404, 422 ani 500, więc ustalenia 1.15, 2.1 i 3.1 nie są wychwytywane przez zestaw testów.

---

## Podsumowanie zbiorcze

| etykieta | liczba | numery ustaleń |
|---|---|---|
| `[BŁĄD]` | 8 | 1.1, 1.2, 1.3, 1.13, 1.15, 2.1, 3.1, 3.2 |
| `[BRAK]` | 4 | 1.6, 1.7, 1.8, 1.9 |
| `[ROZBIEŻNOŚĆ]` | 8 | 1.10, 1.11, 1.12, 1.14, 2.5, 2.6, 3.3, oraz `customerPaymentLink.locale` w odpowiedziach (konsekwencja 1.3) |
| `[NADMIAR]` | 2 | 1.4, 1.5 |
| `[INFO]` | 8 | 1.16, 1.17, 2.2, 2.3, 2.4, 2.7, 3.4, 3.5 |

Łącznie: **30 ustaleń**. Pola `payment` request: 7 zaimplementowanych zgodnie, 7 brakujących, 1 nadmiarowe, 5 z rozbieżnościami typu/ograniczeń. URL-e wszystkich trzech endpointów są zgodne ze specyfikacją.

### Pięć najpoważniejszych rzeczy

1. **3.1 — `POST /payment/cancel` ignoruje zadeklarowaną odpowiedź 400.** Nieudane anulowanie linku zwraca pozornie poprawne DTO (`isActive = false`, `status = null`) zamiast wyjątku. `src/Lib/Api.php:244-248`.
2. **2.1 / 1.15 — `validateResponse` obsługuje tylko 422 i 500.** 400, 401, 403, 404, 406, 503 i 504 przechodzą bez wyjątku we wszystkich trzech endpointach; konsument dostaje DTO wypełnione wartościami domyślnymi (`''`, `0`, `false`). `src/Lib/Api.php:236-249` vs `ing-pay-api-topics/05-kody-odpowiedzi-http.md`.
3. **1.3 — enum `Lang` rozjeżdża się ze specyfikacją `locale`.** Brak `uk`, `bg`, `sv`, nadmiarowe `ua`. Odpowiedź API z `customer.locale: "uk"` powoduje niezłapany `ValueError` w `BaseDto::castAttribute` (`src/DTO/BaseDto.php:58`) i wysypanie wszystkich trzech metod. `src/Types/Lang.php:7-22`.
4. **1.1 / 3.2 — `array_merge_recursive` psuje payload przy jawnym `serviceId`.** W `PaymentDto` daje tablicę zamiast stringa, w `CancelPaymentDto` — literalne `"Array"`. `src/DTO/Api/PaymentDto.php:71-73`, `src/DTO/Api/CancelPaymentDto.php:39-41`.
5. **1.2 / 1.13 — semantyka `validTo` jest zgubiona w obie strony.** W requeście `array_filter` usuwa `validTo: null`, więc nie da się utworzyć linku bez wygaśnięcia; w odpowiedzi `POST /payment` cast `'int'` zamienia `null` na `0`, czyli timestamp 1970. `src/DTO/BaseDto.php:30-32`, `src/DTO/Casts/PaymentDto.php:46`.
