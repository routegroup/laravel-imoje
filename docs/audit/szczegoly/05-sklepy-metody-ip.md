# Sklepy, metody płatności, zaufane IP

Audyt zgodności `routegroup/laravel-imoje` ze specyfikacją **ING Pay RESTful API v1.0.2**
(OpenAPI 3.1, `spec/ing-pay-api.json`, `spec/api-components.json`, rozdziały `ing-pay-api-topics/`).

Zakres: 5 endpointów (metody płatności, dane sklepów, zaufane IP) + kompletność enumów
`PaymentMethod` / `PaymentMethodCode`.

Repo (worktree): `/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a`,
gałąź `agent/ing-pay-api-audit-5dd10a`, HEAD `b8fb4bf`.

Wszystkie ścieżki plików repo są podane względem katalogu worktree.

---

## 0. Ustalenia przekrojowe (dotyczą wszystkich 5 endpointów)

### 0.1 Bazowy URL — ZGODNE

Spec: serwery `https://api.pay.ing.pl/v1/merchant` (produkcja) i
`https://api.sandbox.pay.ing.pl/v1/merchant` (sandbox), autoryzacja HTTP Bearer
(`components.securitySchemes`).

Repo: `src/Types/Environment.php:15-16` zwraca `https://api.pay.ing.pl/v1` /
`https://api.sandbox.pay.ing.pl/v1`, a każda metoda `Url` dokleja segment `merchant` i
`merchantId`. Sklejony URL jest zgodny. Bearer: `src/Lib/Api.php:228-230`
(`Http::asJson()->acceptJson()->withToken($this->config->apiKey)`).

- `[INFO]` Potwierdzone sklejenia (weryfikacja w `tests/Lib/UrlTest.php:11,65-79`, baza
  `https://api.sandbox.pay.ing.pl/v1/merchant/$merchant_id$`):
  - `src/Lib/Url.php:157-163` → `/service/{serviceId}/get-payment-methods`
  - `src/Lib/Url.php:136-145` → `/service/{serviceId}`
  - `src/Lib/Url.php:147-155` → `/services`
  - `src/Lib/Url.php:165-174` → `/settings/ips`

### 0.2 Obsługa kodów błędów

- `[BŁĄD]` `src/Lib/Api.php:236-249` — `validateResponse()` reaguje wyłącznie na **422** i **500**.
  Spec (`ing-pay-api-topics/05-kody-odpowiedzi-http.md`) definiuje komplet: `400`, `401`, `403`,
  `404`, `406`, `422`, `500`, `503`, `504`. Dla wszystkich pozostałych `match` kończy się
  `default => null` (`src/Lib/Api.php:247`), więc **żaden wyjątek nie leci**, a metoda idzie dalej i
  buduje DTO z ciała odpowiedzi błędu. Konsument dostaje obiekt wyglądający na poprawny.
  Skutki per endpoint opisane w sekcjach 1, 3, 4.
  Dodatkowo `ApiErrorException` zawsze ustawia kod `422` (`src/Exceptions/ApiErrorException.php:33`),
  więc nawet dla 500 konsument widzi `getCode() === 422`.

- `[INFO]` W samej specyfikacji OpenAPI te 5 endpointów deklaruje **wyłącznie odpowiedź 200**
  (`api-endpoints/*.json` → `responses` zawiera tylko klucz `200`). Komplet kodów błędów jest
  opisany wyłącznie narracyjnie w rozdziale 05. Nie zmienia to ryzyka — `401`/`403`/`404` są
  realne dla każdego zapytania.

### 0.3 Host CDN dla logotypów

- `[BŁĄD]` `src/Types/Environment.php:43-46` — `cdnUrl()` zwraca `https://data.imoje.pl`.
  Specyfikacja konsekwentnie używa `https://cdn.pay.ing.pl` (18 wystąpień w plikach spec; m.in.
  `components.responses.get-payment-methods-response-200` → `image[].png` i
  `components.responses.service-response-200` → `service.paymentMethods[].paymentMethodImage`).
  Dotyczy `PaymentMethodCode::getLogo()` (`src/Types/PaymentMethodCode.php:67-76`), czyli
  logotypów budowanych lokalnie dla metod zwracanych przez `get-payment-methods` i `GET /service`.
  Commit `b6821f4` („fix(urls): migrate api and paywall hosts to pay.ing.pl") zmigrował hosty API
  i paywalla, ale **pominął CDN**.
  Ten sam stary host jest zaszyty w fixture'ach: `src/Factories/Casts/ServiceDataDtoFactory.php:32-33`
  oraz `src/Factories/Responses/PaymentMethodsResponseDtoFactory.php:21,31`.

### 0.4 Walidacja parametrów ścieżki

- `[ROZBIEŻNOŚĆ]` `components.schemas.serviceId` wymaga `format: uuid`, `maxLength: 36` i
  `pattern: ^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$` (UUID v4).
  Repo nie waliduje ani nie koduje segmentów ścieżki — `src/Lib/Url.php:136-145,157-163` skleja
  URL przez `implode('/')`. `serviceId` ze znakiem `/`, `?` lub `#` rozjedzie ścieżkę zapytania.

### 0.5 Pokrycie testami

- `[INFO]` `tests/Lib/ApiTest.php:34-41` — jedyna asercja dla wszystkich endpointów to
  `expect($response)->toBeInstanceOf($instance)`. Nie jest weryfikowane: ciało żądania, metoda HTTP,
  ani żadne pole odpowiedzi. `Http::fake()` kluczowany URL-em (`tests/Lib/ApiTest.php:39`) dopasowuje
  dowolną metodę HTTP, więc rozróżnienie GET vs PUT na `/settings/ips` również nie jest testowane.
- `[INFO]` `tests/Lib/ApiSpecTest.php` (testy kształtu odpowiedzi względem specyfikacji) pokrywa
  wyłącznie `GET /payment` i `GET /transaction` — **żaden z 5 endpointów tego audytu nie ma tam testu**.

### 0.6 Dokumentacja konsumencka

- `[INFO]` `docs/api.md` (6 linii) odsyła do archiwalnych dokumentów imoje
  (`imojeapi.docs.apiary.io`), nie do aktualnej specyfikacji ING Pay. Żaden z 5 endpointów nie jest
  w `docs/` opisany.

### 0.7 Spójność sygnatur

- `[INFO]` `src/Lib/Api.php:179-182` — `getPaymentMethods(string $serviceId, GetPaymentMethodsDto $dto)`
  ma odwrotną kolejność argumentów niż `createRefund(RefundDto $dto, string $transactionId)`
  (`src/Lib/Api.php:91-94`) i `canRefund(CanRefundDto $dto, string $transactionId)` (`src/Lib/Api.php:168-171`).
- `[INFO]` `Config::$serviceId` (`src/Lib/Config.php:13`) istnieje, ale `getService()` i
  `getPaymentMethods()` zawsze wymagają jawnego `serviceId` — brak domyślki z konfiguracji.

### 0.8 Tolerancja nieznanych pól — ZGODNE

- `[INFO]` `components.schemas.additionalProperties` wymaga wprost: „Twój schemat walidacyjny
  odpowiedzi API powinien zawsze dopuszczać nowe parametry". `BaseDto` dziedziczy po
  `Illuminate\Support\Fluent`, więc nieznane pola odpowiedzi są zachowywane
  (`src/DTO/BaseDto.php:12,18-22`). Zachowanie potwierdzone testem
  `tests/Lib/ApiSpecTest.php:83-97`. To jest zgodne ze specyfikacją.
  **Wyjątek:** pola rzutowane na enum — patrz sekcja 6.3.

---

## 1. `POST /{merchantId}/service/{serviceId}/get-payment-methods`

**Werdykt: ROZBIEŻNOŚCI** — metoda, URL i rozpakowanie kolekcji są poprawne, ale request body nie
egzekwuje enumów `device`/`locale` ani pól wymaganych, a odpowiedź jest nietypowanym przelotem.

Spec: `api-endpoints/post-merchantid-service-serviceid-get-payment-methods.json`,
body `components.schemas.get-payment-methods`, odpowiedź
`components.responses.get-payment-methods-response-200`.
Repo: `src/Lib/Api.php:179-188`, `src/DTO/Api/GetPaymentMethodsDto.php`,
`src/DTO/Responses/PaymentMethodsResponseDto.php`.

### 1.1 Metoda, URL, parametry ścieżki

| pole | spec | repo | status |
|---|---|---|---|
| metoda HTTP | `POST` | `->post($url, $dto->toArray())` — `src/Lib/Api.php:184` | ZGODNE |
| ścieżka | `/{merchantId}/service/{serviceId}/get-payment-methods` | `src/Lib/Url.php:157-163` | ZGODNE |
| `merchantId` (path, required, string) | z konfiguracji | `src/Lib/Url.php:139-141` przez `createServiceUrl` | ZGODNE |
| `serviceId` (path, required, `serviceId` UUID v4) | — | argument `string $serviceId`, brak walidacji wzorca | ROZBIEŻNOŚĆ (p. 0.4) |
| parametry query | brak w spec | brak | ZGODNE |

### 1.2 Request body

| pole | spec | repo | status |
|---|---|---|---|
| `amount` | `integer`, **required**, w groszach (`components.schemas.amount`) | cast `'int'` — `src/DTO/Api/GetPaymentMethodsDto.php:27`; brak wymuszenia obecności | ROZBIEŻNOŚĆ |
| `currency` | `string`, **required**, enum 10 wartości, `maxLength: 3` (`components.schemas.currency`) | cast `Currency::class` — `src/DTO/Api/GetPaymentMethodsDto.php:28`; enum ma 12 wartości | NADMIAR (p. 6.4) |
| `device` | `string`, opcjonalne, **enum `desktop` / `mobile`** | `string|null` bez enuma i bez walidacji — `src/DTO/Api/GetPaymentMethodsDto.php:15,37` | ROZBIEŻNOŚĆ |
| `locale` | opcjonalne, **enum 17 wartości** (`components.schemas.locale`) | `string|null` bez enuma — `src/DTO/Api/GetPaymentMethodsDto.php:16,38`; istniejący `Lang` nieużyty | ROZBIEŻNOŚĆ |
| pola nadmiarowe w DTO | — | brak | ZGODNE |

**Ustalenia:**

- `[ROZBIEŻNOŚĆ]` `src/DTO/Api/GetPaymentMethodsDto.php:16,38` — `locale` jest zwykłym stringiem,
  mimo że repo ma gotowy enum `src/Types/Lang.php`. Wpisanie literówki nie zostanie wychwycone
  lokalnie — błąd wróci dopiero jako 422 z API. (Sam `Lang` też rozjeżdża się ze spec — p. 6.4.)
- `[ROZBIEŻNOŚĆ]` `src/DTO/Api/GetPaymentMethodsDto.php:15,37` — `device` analogicznie: brak enuma
  `desktop`/`mobile`, brak `Types\Device`.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Api/GetPaymentMethodsDto.php:24` ustawia `protected bool $allowNull = true`.
  Skutkuje to w `src/DTO/BaseDto.php:30-32` wywołaniem `array_filter($attributes)` **na wszystkich**
  atrybutach, a w `src/DTO/BaseDto.php:38-41` zwróceniem `null` dla każdej wartości `empty()`.
  Konsekwencja: `amount => 0` (albo `'0'`) zostaje po cichu **usunięte z body**, mimo że jest polem
  wymaganym. Zamiast lokalnego błędu konsument dostaje 422 z API, a DTO nie sygnalizuje braku.
  Brak jakiejkolwiek walidacji `required` dla `amount` i `currency` po stronie biblioteki.
- `[INFO]` `src/Factories/Api/GetPaymentMethodsDtoFactory.php:17-20` generuje tylko `amount`
  i `currency` — `device`/`locale` nie są pokryte żadnym testem.

### 1.3 Odpowiedź 200

Spec: odpowiedzią jest **tablica najwyższego poziomu** (`schema.type: array`) obiektów metod.

| pole | spec | repo | status |
|---|---|---|---|
| kształt korzenia | `array` obiektów metod | `['methods' => $raw]` — `src/DTO/Responses/PaymentMethodsResponseDto.php:22-25` | ZGODNE (poprawne rozpakowanie) |
| `label` | `string` | przelot w `methods`, bez typu | ROZBIEŻNOŚĆ |
| `isOnline` | `boolean` | przelot, bez castu `bool` | ROZBIEŻNOŚĆ |
| `image[]` | `array` obiektów `{png}` | przelot | ROZBIEŻNOŚĆ |
| `priority` | `integer` | przelot, bez castu `int` | ROZBIEŻNOŚĆ |
| `channels[].paymentMethod` | enum `paymentMethod` | przelot, **bez castu na `PaymentMethod`** | ROZBIEŻNOŚĆ |
| `channels[].paymentMethodCode` | enum `paymentMethodCode` | przelot, **bez castu na `PaymentMethodCode`** | ROZBIEŻNOŚĆ |
| `channels[].label` / `priority` / `isOnline` / `image` | string / integer / boolean / array | przelot | ROZBIEŻNOŚĆ |

**Ustalenia:**

- `[ROZBIEŻNOŚĆ]` `src/DTO/Responses/PaymentMethodsResponseDto.php:11,19-26` — cała odpowiedź to jedna
  nietypowana tablica pod kluczem `methods`. Nie ma sub-DTO metody ani kanału, żaden z 11 pól
  specyfikacji nie jest rzutowany. Konsument musi samodzielnie znać kształt i ręcznie mapować
  `isOnline` (kluczowy parametr wg rozdziału 05, służący do unikania błędów 406).
- `[BŁĄD]` Przy statusie innym niż 200/422/500 (p. 0.2) `validateResponse` przepuszcza odpowiedź,
  a `src/DTO/Responses/PaymentMethodsResponseDto.php:22-25` wpycha **ciało błędu** pod klucz `methods`.
  Pętla po `$dto->methods` iteruje wtedy po polach komunikatu błędu zamiast po metodach płatności.
- `[INFO]` `src/Factories/Responses/PaymentMethodsResponseDtoFactory.php:16-37` jako jedyny fixture
  w tym zakresie ma kształt zgodny ze spec (lista najwyższego poziomu) — odwzorowuje przykład
  `get-payment-methods-response-200.example` jeden do jednego (poza hostem CDN, p. 0.3).

---

## 2. `GET /{merchantId}/service/{serviceId}`

**Werdykt: ROZBIEŻNOŚCI** — kształt i komplet pól są poprawne, ale `created` traci dopuszczalny
`null`, a `paymentMethods` pozostaje surową tablicą.

Spec: `api-endpoints/get-merchantid-service-serviceid.json`,
odpowiedź `components.responses.service-response-200`.
Repo: `src/Lib/Api.php:190-197`, `src/DTO/Responses/ServiceResponseDto.php`,
`src/DTO/Casts/ServiceDataDto.php`.

### 2.1 Metoda, URL, parametry

| pole | spec | repo | status |
|---|---|---|---|
| metoda HTTP | `GET` | `->get($url)` — `src/Lib/Api.php:193` | ZGODNE |
| ścieżka | `/{merchantId}/service/{serviceId}` | `src/Lib/Url.php:136-145` | ZGODNE |
| `merchantId` (path, required) | — | z `Config` | ZGODNE |
| `serviceId` (path, required, UUID v4) | — | argument, brak walidacji wzorca | ROZBIEŻNOŚĆ (p. 0.4) |
| request body | brak | brak | ZGODNE |

### 2.2 Odpowiedź 200

Spec: obiekt `{ service: { ... } }`.

| pole | spec | repo | status |
|---|---|---|---|
| kształt korzenia | obiekt z kluczem `service` | cast `'service' => ServiceDataDto::class` — `src/DTO/Responses/ServiceResponseDto.php:20-22` | ZGODNE |
| `service.id` | `serviceId` (string, UUID v4, maxLength 36) | `@property-read string $id` — `src/DTO/Casts/ServiceDataDto.php:12`, bez castu i walidacji | ZGODNE (INFO) |
| `service.created` | `type: ["integer", "null"]` — **dopuszcza null** (`components.schemas.created`) | cast `'created' => 'int'` — `src/DTO/Casts/ServiceDataDto.php:24` | **BŁĄD** |
| `service.isActive` | `boolean` | cast `'isActive' => 'bool'` — `src/DTO/Casts/ServiceDataDto.php:25` | ROZBIEŻNOŚĆ |
| `service.paymentMethods` | `array` obiektów o 9 polach | `@property-read array` — `src/DTO/Casts/ServiceDataDto.php:15`, **brak castu** | ROZBIEŻNOŚĆ |
| `…paymentMethods[].paymentMethod` | enum `paymentMethod` | przelot, bez castu na enum | ROZBIEŻNOŚĆ |
| `…paymentMethods[].paymentMethodCode` | enum `paymentMethodCode` | przelot, bez castu na enum | ROZBIEŻNOŚĆ |
| `…paymentMethods[].isActive` / `isOnline` | `boolean` | przelot | ROZBIEŻNOŚĆ |
| `…paymentMethods[].description` | `string` | przelot | ROZBIEŻNOŚĆ |
| `…paymentMethods[].currency` | enum `currency` | przelot, bez castu na `Currency` | ROZBIEŻNOŚĆ |
| `…paymentMethods[].transactionLimits.maxTransaction{type,value}` | obiekt `{string, number}` | przelot | ROZBIEŻNOŚĆ |
| `…paymentMethods[].transactionLimits.minTransaction{type,value}` | obiekt `{string, number}` | przelot | ROZBIEŻNOŚĆ |
| `…paymentMethods[].paymentMethodImage` | `array` obiektów `{png}` | przelot | ROZBIEŻNOŚĆ |
| `…paymentMethods[].paymentMethodCodeImage` | `array` obiektów `{png}` | przelot | ROZBIEŻNOŚĆ |
| pola nadmiarowe w DTO | — | brak | ZGODNE |

**Ustalenia:**

- `[BŁĄD]` `src/DTO/Casts/ServiceDataDto.php:24` — `'created' => 'int'` przy domyślnym
  `allowNull = false` (`src/DTO/BaseDto.php:16`). W `src/DTO/BaseDto.php:27` brakujący klucz
  podstawia `null`, a `src/DTO/BaseDto.php:62` wykonuje `(int) null` → **`0`**. Spec jawnie
  dopuszcza `null` dla `created` (`components.schemas.created`: `type: ["integer","null"]`).
  Efekt: `null` z API staje się timestampem `0` (1970-01-01), czyli pozornie poprawną datą.
  Konsument nie ma możliwości odróżnienia „brak daty" od „data zerowa".
  Deklaracja `@property-read int $created` (`src/DTO/Casts/ServiceDataDto.php:13`) utrwala tę
  niezgodność w typie — powinno być `?int`.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Casts/ServiceDataDto.php:25` — `'isActive' => 'bool'` z tym samym
  mechanizmem: brak pola w odpowiedzi daje `(bool) null === false`, czyli „sklep nieaktywny".
  Brak pola i wyłączony sklep są nierozróżnialne.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Casts/ServiceDataDto.php:15,23-26` — `paymentMethods` to surowa tablica.
  Specyfikacja opisuje tu 9 pól na kanał (w tym zagnieżdżone `transactionLimits`), z czego dwa to
  enumy, a `isActive`+`isOnline` są wg rozdziału 05 kluczowe dla uniknięcia błędu 406. Nic z tego
  nie jest typowane ani rzutowane.
- `[INFO]` Niespójność **wewnątrz samej specyfikacji**: `service-response-200` deklaruje
  `paymentMethodImage` / `paymentMethodCodeImage` jako `type: array` obiektów `{png}`, ale
  `example` pokazuje je jako pojedynczy obiekt `{"png": "..."}`. Fixture repo
  (`src/Factories/Casts/ServiceDataDtoFactory.php:32-33`) trzyma się schematu (tablica), nie przykładu.
  Warto to potwierdzić u dostawcy przed dodaniem typowania.

---

## 3. `GET /{merchantId}/services`

**Werdykt: ROZBIEŻNOŚCI** — rozpakowanie kolekcji jest **poprawne**, ale błędne odpowiedzi są
po cichu zamieniane na pustą listę, `toArray()` nie serializuje zagnieżdżonych DTO, a fixture
testowy ma zły kształt.

Spec: `api-endpoints/get-merchantid-services.json`,
odpowiedź `components.responses.services-response-200`.
Repo: `src/Lib/Api.php:199-206`, `src/DTO/Responses/ServicesResponseDto.php`.

### 3.1 Metoda, URL, parametry

| pole | spec | repo | status |
|---|---|---|---|
| metoda HTTP | `GET` | `->get($url)` — `src/Lib/Api.php:202` | ZGODNE |
| ścieżka | `/{merchantId}/services` | `src/Lib/Url.php:147-155` | ZGODNE |
| `merchantId` (path, required) | — | z `Config` | ZGODNE |
| parametry query / body | brak | brak | ZGODNE |

### 3.2 Odpowiedź 200

Spec: **tablica najwyższego poziomu** elementów `{ service: { ... } }` (czyli każdy element jest
opakowany kluczem `service`).

| pole | spec | repo | status |
|---|---|---|---|
| kształt korzenia | `array` elementów `{service: {...}}` | `array_map(... $item['service'] ?? [], array_is_list($raw) ? $raw : [])` — `src/DTO/Responses/ServicesResponseDto.php:24-28` | ZGODNE (poprawne rozpakowanie 2 poziomów) |
| element → `service` | obiekt | `new ServiceDataDto($item['service'] ?? [])` — `src/DTO/Responses/ServicesResponseDto.php:25` | ZGODNE |
| `service.id` / `created` / `isActive` / `paymentMethods` | jak w `service-response-200` | ten sam `ServiceDataDto` | dziedziczy ustalenia z sekcji 2.2 |
| odpowiedź nie będąca listą | — | cicho → `[]` | **BŁĄD** |

**Ustalenia:**

- `[BŁĄD]` `src/DTO/Responses/ServicesResponseDto.php:27` — `array_is_list($raw) ? $raw : []`.
  W połączeniu z p. 0.2 (brak obsługi 401/403/404/406/503/504) odpowiedź błędu — która jest
  **obiektem**, nie listą — przechodzi przez `validateResponse` bez wyjątku i zamienia się
  w `services === []`. Konsument dostaje „merchant nie ma żadnych sklepów" zamiast informacji
  o braku uprawnień albo niedostępności systemu. Cicha awaria bez żadnego sygnału.
- `[BŁĄD]` `tests/Lib/ApiTest.php:156-158` + `src/Factories/Responses/ServicesResponseDtoFactory.php:16` —
  fixture `definition()` zwraca `[]`, więc `ServicesResponseDto::factory()->make()->toArray()` daje
  `['services' => []]`, czyli **obiekt** `{"services":[]}`. Taki payload jest podstawiany jako
  odpowiedź API, mimo że spec zwraca tu **listę**. Test przechodzi przez gałąź
  `array_is_list(...) === false` i zawsze kończy na pustej kolekcji.
  W efekcie najbardziej ryzykowna logika pakietu — dwupoziomowe rozpakowanie kolekcji —
  **nie jest pokryta żadnym testem**, a fixture utrwala nieprawidłowy kształt odpowiedzi.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Responses/ServicesResponseDto.php:28` zapisuje do `attributes` tablicę
  **obiektów** `ServiceDataDto`. `BaseDto::toArray()` (`src/DTO/BaseDto.php:70-75`) deleguje do
  `Utils::transformValues()`, które iteruje **wyłącznie po najwyższym poziomie**
  (`src/Lib/Utils.php:49-68` — brak rekurencji). Klucz `services` jest zwykłą tablicą PHP, nie
  `Arrayable`, więc nie jest przekształcany. `ServicesResponseDto::toArray()` zwraca zatem
  `['services' => [ServiceDataDto, ServiceDataDto, ...]]` — obiekty zamiast tablic. Łamie to
  kontrakt `toArray()` i uniemożliwia round-trip przez fixture (jedyny powód, dla którego test
  z poprzedniego punktu nie wybucha, to pusta kolekcja).
- `[ROZBIEŻNOŚĆ]` `src/DTO/Responses/ServicesResponseDto.php:25` — callback ma type hint
  `fn (array $item)`, a plik deklaruje `declare(strict_types=1)` (`src/DTO/Responses/ServicesResponseDto.php:3`).
  Lista zawierająca element nie będący tablicą (np. lista stringów) rzuci surowy `TypeError`
  zamiast wyjątku domenowego pakietu.

---

## 4. `GET /{merchantId}/settings/ips`

**Werdykt: ROZBIEŻNOŚCI** — kształt odpowiedzi odczytany prawidłowo, ale cast `'array'` jest
w `BaseDto` operacją pustą, a brak obsługi błędów tworzy ryzyko wyzerowania listy IP.

Spec: `api-endpoints/get-merchantid-settings-ips.json` (schemat inline, nie `$ref`).
Repo: `src/Lib/Api.php:208-215`, `src/DTO/Responses/TrustedIpsResponseDto.php`.

### 4.1 Metoda, URL, parametry

| pole | spec | repo | status |
|---|---|---|---|
| metoda HTTP | `GET` | `->get($url)` — `src/Lib/Api.php:211` | ZGODNE |
| ścieżka | `/{merchantId}/settings/ips` | `src/Lib/Url.php:165-174` | ZGODNE |
| `merchantId` (path, required) | — | z `Config` | ZGODNE |
| parametry query / body | brak | brak | ZGODNE |

### 4.2 Odpowiedź 200

Spec: **obiekt** `{ trustedIps: array }` z `required: ["trustedIps"]`.
(Uwaga: wbrew pierwotnemu założeniu audytu ten endpoint **nie** zwraca kolekcji najwyższego
poziomu — zwraca obiekt z tablicą w środku. Repo traktuje go poprawnie jako obiekt.)

| pole | spec | repo | status |
|---|---|---|---|
| kształt korzenia | obiekt `{trustedIps}` | `ResponseDto` → `collect()->toArray()` — `src/DTO/Responses/ResponseDto.php:17` | ZGODNE |
| `trustedIps` | `array`, **required** | cast `'trustedIps' => 'array'` — `src/DTO/Responses/TrustedIpsResponseDto.php:20-22` | ROZBIEŻNOŚĆ |
| elementy `trustedIps` | brak `items` w spec (przykład: `["127.0.0.1","10.10.10.10"]`) | brak walidacji | ZGODNE |
| `status` | nie występuje w odpowiedzi GET | zadeklarowane w docblocku — `src/DTO/Responses/TrustedIpsResponseDto.php:12` | ROZBIEŻNOŚĆ (p. 5.2) |

**Ustalenia:**

- `[ROZBIEŻNOŚĆ]` `src/DTO/Responses/TrustedIpsResponseDto.php:21` — cast `'array'` **nie istnieje**
  w `BaseDto`. `src/DTO/BaseDto.php:61-67` obsługuje tylko `int|integer`, `real|float|double`,
  `string`, `bool|boolean`; `'array'` wpada w `default => $value` i zwraca wartość bez zmian.
  Skutki: (a) odpowiedź nie-tablicowa nie jest koercjonowana, (b) przy braku klucza
  `src/DTO/BaseDto.php:27` wstawia `null`, więc `$dto->trustedIps === null`, a nie `[]` —
  wbrew `required` w spec i wbrew docblockowi `@property-read array $trustedIps`
  (`src/DTO/Responses/TrustedIpsResponseDto.php:11`). Konsument iterujący po wyniku dostanie
  `foreach` po `null`.
- `[BŁĄD]` Połączenie p. 0.2 i powyższego daje konkretne ryzyko operacyjne. Przy `401`/`403`
  (wygasły/niewłaściwy token, brak uprawnień) `getTrustedIps()` **nie rzuca wyjątku**, a zwraca DTO
  z `trustedIps === null`. Typowy wzorzec konsumenta (odczyt listy → dodanie adresu → zapis przez
  `putTrustedIps`, `src/Lib/Api.php:217-224`) wyśle wtedy PUT z listą zawierającą **wyłącznie nowy
  adres**, kasując wszystkie dotychczasowe zaufane IP. Jest to lista kontroli dostępu, więc skutek
  jest bezpośrednio bezpieczeństwowy (odcięcie własnych serwerów lub nieumyślne rozszczelnienie).

---

## 5. `PUT /{merchantId}/settings/ips`

**Werdykt: ROZBIEŻNOŚCI** — metoda, URL i body są zgodne, ale odpowiedź jest obsługiwana przez DTO
przeznaczone dla GET, a test podstawia payload niezgodny ze specyfikacją.

Spec: `api-endpoints/put-merchantid-settings-ips.json`, body `components.schemas.put-trusted-ips`.
Repo: `src/Lib/Api.php:217-224`, `src/DTO/Api/PutTrustedIpsDto.php`,
`src/DTO/Responses/TrustedIpsResponseDto.php`.

### 5.1 Metoda, URL, request body

| pole | spec | repo | status |
|---|---|---|---|
| metoda HTTP | `PUT` | `->put($url, $dto->toArray())` — `src/Lib/Api.php:220` | ZGODNE |
| ścieżka | `/{merchantId}/settings/ips` | `src/Lib/Url.php:165-174` (wspólna z GET) | ZGODNE |
| `merchantId` (path, required) | — | z `Config` | ZGODNE |
| `requestBody.required` | `true` | zawsze wysyłane body | ZGODNE |
| `trustedIps` (body) | `array`, **required** | `@property-read array`, brak castów — `src/DTO/Api/PutTrustedIpsDto.php:12,23` | ROZBIEŻNOŚĆ |
| elementy `trustedIps` | brak `items` w spec | brak walidacji formatu IP | ROZBIEŻNOŚĆ |
| pola nadmiarowe w DTO | — | brak | ZGODNE |

**Ustalenia:**

- `[ROZBIEŻNOŚĆ]` `src/DTO/Api/PutTrustedIpsDto.php:16-29` — DTO nie ma castów ani
  `allowNull`, więc `castAttributes` (`src/DTO/BaseDto.php:24-35`) jest przelotem. Pozytywnie:
  `trustedIps => []` przechodzi nienaruszone, więc **celowe wyczyszczenie listy jest możliwe**
  (gdyby `allowNull` było `true`, `array_filter` usunęłoby pustą tablicę i wysłało `{}`).
  Negatywnie: `new PutTrustedIpsDto([])` wysyła `{}` bez żadnego ostrzeżenia, mimo że
  `trustedIps` jest polem wymaganym; brak też walidacji, że elementy są adresami IP.

### 5.2 Odpowiedź 200

Spec: obiekt `{ status: string }` z `required: ["status"]`, przykład `{"status":"success"}`.
**Kształt inny niż w GET** — w odpowiedzi PUT **nie ma** pola `trustedIps`.

| pole | spec | repo | status |
|---|---|---|---|
| kształt korzenia | obiekt `{status}` | `TrustedIpsResponseDto` (to samo DTO co dla GET) — `src/Lib/Api.php:223` | ROZBIEŻNOŚĆ |
| `status` | `string`, **required** | tylko docblock `@property-read string|null` — `src/DTO/Responses/TrustedIpsResponseDto.php:12`; brak castu `'string'` | ROZBIEŻNOŚĆ |
| `trustedIps` | **nie występuje** | cast wymusza klucz → `trustedIps === null` w DTO | ROZBIEŻNOŚĆ |

**Ustalenia:**

- `[ROZBIEŻNOŚĆ]` `src/Lib/Api.php:208-224` — `getTrustedIps()` i `putTrustedIps()` zwracają
  **ten sam typ** `TrustedIpsResponseDto`, mimo że spec definiuje dla nich dwa różne, rozłączne
  kształty odpowiedzi (`{trustedIps}` vs `{status}`). DTO jest unią obu
  (`src/DTO/Responses/TrustedIpsResponseDto.php:11-12`), więc typ nie mówi konsumentowi, które pole
  będzie wypełnione. Po PUT `trustedIps` jest zawsze `null` (mechanizm z p. 4.2), po GET `status`
  jest zawsze `null`.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Responses/TrustedIpsResponseDto.php:20-22` — brak castu dla `status`,
  mimo że spec deklaruje `type: string` i `required`.
- `[BŁĄD]` `tests/Lib/ApiTest.php:170-174` — test PUT podstawia jako odpowiedź
  `TrustedIpsResponseDto::factory()->make()->toArray()`, czyli
  `{"trustedIps":["127.0.0.1"]}` (`src/Factories/Responses/TrustedIpsResponseDtoFactory.php:16-19`).
  Specyfikacja zwraca tu `{"status":"success"}`. Prawidłowy kształt odpowiedzi PUT nie jest
  **w ogóle** zweryfikowany, a fixture utrwala nieprawidłowy. Dodatkowo `Http::fake()` kluczowany
  samym URL-em (`tests/Lib/ApiTest.php:39`) jest wspólny dla GET i PUT na `/settings/ips`, więc
  test nie dowodzi nawet, że wysłano metodę `PUT`.

---

## 6. Kompletność enumów `PaymentMethod` i `PaymentMethodCode`

Źródła spec: `components.schemas.paymentMethod`, `components.schemas.paymentMethodCode`,
`components.schemas.visibleMethod` / `visibleMethodTransaction`, tabele w
`ing-pay-api-topics/03-metody-i-kanaly-realizacji-transakcji.md` oraz
`ing-pay-paywall-topics/04-wyswietlanie-wybranych-metod-platnosci-na-bramce.md`.

### 6.1 `PaymentMethod` (`src/Types/PaymentMethod.php`)

Repo: 10 wartości. Spec `paymentMethod`: 8 wartości. Spec `visibleMethod` / `visibleMethodTransaction`:
8 wartości (**inny zestaw**).

| wartość | `paymentMethod` (spec) | `visibleMethod` (spec) | tabela rozdz. 03 | repo (`src/Types/PaymentMethod.php`) | status |
|---|---|---|---|---|---|
| `blik` | tak | tak | tak | `BLIK` :11 | ZGODNE |
| `pbl` | tak | tak | tak | `PAY_BY_LINK` :10 | ZGODNE |
| `card` | tak | tak | tak | `CARD` :9 | ZGODNE |
| `wallet` | tak | tak | tak | `WALLET` :16 | ZGODNE |
| `imoje_paylater` | tak | tak | tak | `PAYLATER` :12 | ZGODNE |
| `wt` | tak | tak | tak | `WIRE_TRANSFER` :14 | ZGODNE |
| `imoje_installments` | tak | tak | tak | `IMOJE_INSTALLMENTS` :18 | ZGODNE |
| `ing` | **tak** | **nie** | tak | `ING` :15 | ZGODNE / pułapka |
| `lease` | **nie** | **tak** | **nie** | `LEASE` :13 | NADMIAR (uzasadniony) |
| `visa_mobile` | **nie** | **nie** | nie (to kanał `wallet`) | `VISA_MOBILE` :17 | **BŁĄD** |

**W spec, brak w repo:** brak — repo pokrywa komplet 8 wartości `paymentMethod` i komplet 8
wartości `visibleMethod`.
**W repo, brak w spec (`paymentMethod`):** `lease`, `visa_mobile`.
**W repo, brak w spec (`visibleMethod`):** `ing`, `visa_mobile`.

**Ustalenia:**

- `[BŁĄD]` `src/Types/PaymentMethod.php:17` — `VISA_MOBILE = 'visa_mobile'` nie występuje
  **w żadnym** z enumów metod: ani w `paymentMethod`, ani w `visibleMethod`/`visibleMethodTransaction`.
  Wg rozdziału 03 (tabela „Portfel elektroniczy") `visa_mobile` jest **kanałem** metody `wallet`,
  a nie metodą. Jest już poprawnie zadeklarowany jako `PaymentMethodCode::VISA_MOBILE`
  (`src/Types/PaymentMethodCode.php:57`). Przekazanie `PaymentMethod::VISA_MOBILE` w `paymentMethod`
  lub w `visibleMethod` zostanie odrzucone przez API (422) — a typ PHP sugeruje, że to poprawna wartość.
- `[NADMIAR]` `src/Types/PaymentMethod.php:13` — `LEASE = 'lease'` **nie** występuje w
  `components.schemas.paymentMethod` ani w tabeli rozdziału 03, ale **jest** w
  `visibleMethod`/`visibleMethodTransaction` oraz w tabeli paywalla
  (`ing-pay-paywall-topics/04-...md`, wiersz „Leaseing online"). Wartość jest zatem uzasadniona,
  ale wyłącznie w kontekście `visibleMethod`.
- `[ROZBIEŻNOŚĆ]` Repo używa **jednego** enuma `PaymentMethod` dla **dwóch różnych** enumów
  specyfikacji. Różnice są realne i w obie strony: `ing` jest dozwolone w `paymentMethod`, ale **nie**
  w `visibleMethod`; `lease` odwrotnie. Typ PHP nie jest w stanie zapobiec żadnej z tych pomyłek.
  Rozdzielenie na `PaymentMethod` i `VisibleMethod` (albo metoda `isVisibleMethod()`) usunęłoby
  obie pułapki.

### 6.2 `PaymentMethodCode` (`src/Types/PaymentMethodCode.php`)

Repo: 55 wartości. Spec `paymentMethodCode`: 48 wartości.

**W spec, brak w repo: (brak)** — repo pokrywa wszystkie 48 wartości enuma ze specyfikacji:
`blik`, `imoje_twisto`, `paypo`, `blik_paylater`, `pragma_go`, `gpay`, `applepay`, `visa_mobile`,
`paypal`, `ecom3ds`, `oneclick`, `recurring`, `ing`, `mtransfer`, `bzwbk`, `pekao24`, `inteligo`,
`ipko`, `getin`, `noble`, `creditagricole`, `alior`, `millennium`, `bos`, `bnpparibas`, `pocztowy`,
`plusbank`, `bs`, `bspb`, `nest`, `pbs`, `cs`, `mp`, `kb`, `rf`, `pg`, `pf`, `cb`, `uc`, `posta`,
`sporo`, `tatra`, `viamo`, `vub`, `wt`, `wt_split`, `inbank`, `inbank_0`.

**W spec (tabela rozdz. 03), brak w repo:**

| wartość | źródło w spec | repo | status |
|---|---|---|---|
| `card` | `ing-pay-api-topics/03-...md`, wiersz „Płatność kartą": kanały `card`, `oneclick`, `recurring` | **brak** w `src/Types/PaymentMethodCode.php` | **BRAK** |

- `[BRAK]` Kanał `card` jest wymieniony w tabeli rozdziału 03 jako kanał metody `card`, ale
  **nie występuje** w `components.schemas.paymentMethodCode` (48 wartości, sprawdzone programowo).
  Jest to niespójność **wewnątrz specyfikacji**. Repo podąża za enumem OpenAPI, więc również nie ma
  tej wartości. Ryzyko opisane w p. 6.3.

**W repo, brak w spec (7 wartości):**

| case | wartość | linia | komentarz | status |
|---|---|---|---|---|
| `BLIK_ONECLICK` | `blik_oneclick` | `src/Types/PaymentMethodCode.php:12` | brak w enumie i w tabeli rozdz. 03 | NADMIAR |
| `C2P` | `c2p` | :19 | brak w spec; brak logotypu w `getLogoFilename()` | NADMIAR |
| `CITI` | `citi` | :21 | brak w spec (bank nieobecny w tabeli „Przelew online / Polska") | NADMIAR |
| `ENVELO` | `envelo` | :24 | brak w spec | NADMIAR |
| `IDEABANK` | `ideabank` | :27 | brak w spec | NADMIAR |
| `IMOJE_INSTALLMENTS` | `imoje_installments` | :28 | w spec to **metoda** (`paymentMethod`), nie kanał; kanały rat to `inbank`, `inbank_0` | NADMIAR (mylący) |
| `TMOBILE` | `tmobile` | :54 | brak w spec | NADMIAR |

- `[NADMIAR]` Siedem powyższych wartości to pozostałości po starszym katalogu kanałów imoje.
  Szczególnie mylące jest `IMOJE_INSTALLMENTS` (`src/Types/PaymentMethodCode.php:28`), bo
  `imoje_installments` jest w specyfikacji **metodą płatności**, a nie kanałem — ta sama wartość
  istnieje równolegle jako `PaymentMethod::IMOJE_INSTALLMENTS` (`src/Types/PaymentMethod.php:18`).
- `[INFO]` Wartości `oneclick`, `recurring`, `ecom3ds` są w repo oznaczone komentarzem
  „For notification" (`src/Types/PaymentMethodCode.php:62-65`), co sugeruje, że są wyłącznie
  notyfikacyjne. W rzeczywistości wszystkie trzy **są** w `components.schemas.paymentMethodCode`,
  a `oneclick`/`recurring` dodatkowo w tabeli rozdziału 03 jako kanały metody `card`.
  Komentarz jest mylący, same wartości są poprawne.

### 6.3 Mechanika rzutowania enumów — ryzyko

- `[BŁĄD]` `src/DTO/BaseDto.php:49-59` — rzutowanie na enum używa **`$castType::from($value)`**
  (`src/DTO/BaseDto.php:58`), które przy nieznanej wartości rzuca `ValueError`. Enumy
  `PaymentMethod` i `PaymentMethodCode` są użyte jako casty w
  `src/DTO/Casts/TransactionDto.php:63-64` oraz `src/DTO/Api/TransactionDto.php:56-57`.
  Konsekwencje:
  1. Odpowiedź lub notyfikacja z `paymentMethodCode: "card"` (wartość z tabeli rozdziału 03,
     nieobecna w enumie — p. 6.2) wywróci hydratację **niewyłapanym `ValueError`**, a nie
     wyjątkiem domenowym pakietu.
  2. Każdy **nowy** kanał dodany przez ING w przyszłości wywoła ten sam błąd. Jest to wprost
     sprzeczne z wymaganiem specyfikacji (`components.schemas.additionalProperties`):
     „Twój schemat walidacyjny odpowiedzi API powinien zawsze dopuszczać nowe parametry".
  `tryFrom()` z jawną obsługą `null` (dla DTO odpowiedzi) usunęłoby oba ryzyka.
  Uwaga: oba `TransactionDto` leżą poza 5 endpointami tego audytu, ale ryzyko wynika bezpośrednio
  z kompletności enumów, które są przedmiotem tej sekcji.

### 6.4 Enumy powiązane (`Currency`, `Lang`) — osiągalne z `get-payment-methods`

| enum | spec | repo | w spec brak w repo | w repo brak w spec | status |
|---|---|---|---|---|---|
| `currency` | 10 wartości (`components.schemas.currency`) | 12 — `src/Types/Currency.php:9-20` | (brak) | `HRK` :15, `UAH` :14 | NADMIAR |
| `locale` | 17 wartości (`components.schemas.locale`) | 15 — `src/Types/Lang.php:7-21` | `bg`, `sv`, `uk` | `ua` :18 | BRAK + BŁĄD |

- `[NADMIAR]` `src/Types/Currency.php:14-15` — `UAH` i `HRK` nie występują w
  `components.schemas.currency` (ani w tabelach walut rozdziału 03). `HRK` jest poza tym walutą
  wycofaną (Chorwacja przeszła na EUR). Użycie którejkolwiek w `get-payment-methods.currency`
  skończy się 422.
- `[BRAK]` `src/Types/Lang.php` — brakuje trzech wartości wymaganych przez
  `components.schemas.locale`: `bg` (bułgarski), `sv` (szwedzki), `uk` (ukraiński).
- `[BŁĄD]` `src/Types/Lang.php:18` — `case UA = 'ua'`. Specyfikacja używa kodu **`uk`**
  (ISO 639-1 dla języka ukraińskiego); `ua` to kod kraju, nie języka, i nie ma go w enumie `locale`.
  Wartość `ua` zostanie odrzucona przez API. (Enum `Lang` nie jest obecnie użyty w
  `GetPaymentMethodsDto` — p. 1.2 — ale jest używany w `Environment::paywallUrl()`,
  `src/Types/Environment.php:20-33`.)

---

## 7. Podsumowanie ustaleń

| etykieta | liczba |
|---|---|
| `[BRAK]` | 2 |
| `[BŁĄD]` | 10 |
| `[ROZBIEŻNOŚĆ]` | 13 |
| `[NADMIAR]` | 3 |
| `[INFO]` | 9 |
| **razem** | **37** |

### Pięć najpoważniejszych ustaleń

1. **`validateResponse` obsługuje tylko 422 i 500** (`src/Lib/Api.php:236-249`).
   Specyfikacja (rozdz. 05) definiuje dziewięć kodów. Dla `400`, `401`, `403`, `404`, `406`, `503`
   i `504` nie leci żaden wyjątek, a DTO jest budowane z ciała błędu — konsument dostaje obiekt
   wyglądający na poprawną odpowiedź. To źródło ustaleń 2 i 3 poniżej.

2. **Ryzyko wyzerowania listy zaufanych IP** (`src/Lib/Api.php:208-224`,
   `src/DTO/Responses/TrustedIpsResponseDto.php:20-22`). Przy 401/403 `getTrustedIps()` zwraca DTO
   z `trustedIps === null` zamiast rzucić wyjątek. Wzorzec „odczytaj → dodaj → zapisz" wyśle wtedy
   PUT kasujący całą dotychczasową listę kontroli dostępu. Skutek bezpośrednio bezpieczeństwowy.

3. **`GET /services` cicho zwraca pustą kolekcję przy błędzie**
   (`src/DTO/Responses/ServicesResponseDto.php:27`). Ciało błędu nie jest listą, więc
   `array_is_list($raw) ? $raw : []` zamienia 403/404/503 w „merchant nie ma sklepów".
   Pogłębia to brak testu: fixture (`src/Factories/Responses/ServicesResponseDtoFactory.php:16`,
   `tests/Lib/ApiTest.php:156-158`) podstawia obiekt `{"services":[]}` zamiast listy wymaganej przez
   spec, więc dwupoziomowe rozpakowanie kolekcji nie jest pokryte **żadnym** testem.

4. **Rzutowanie enumów przez `from()` łamie wymóg kompatybilności w przód**
   (`src/DTO/BaseDto.php:58`, użyte w `src/DTO/Casts/TransactionDto.php:63-64` i
   `src/DTO/Api/TransactionDto.php:56-57`). Każda wartość spoza enuma — w tym kanał `card`
   z tabeli rozdziału 03, którego brakuje i w specyfikacji OpenAPI, i w repo — kończy się
   niewyłapanym `ValueError`. Specyfikacja wprost wymaga dopuszczania nowych wartości
   (`components.schemas.additionalProperties`).

5. **Host CDN nie został zmigrowany** (`src/Types/Environment.php:45`). `cdnUrl()` zwraca
   `https://data.imoje.pl`, podczas gdy cała specyfikacja używa `https://cdn.pay.ing.pl`
   (18 wystąpień). Dotyka `PaymentMethodCode::getLogo()` (`src/Types/PaymentMethodCode.php:67-76`),
   czyli logotypów dla metod z `get-payment-methods` i `GET /service`. Commit `b6821f4` zmigrował
   hosty API i paywalla, ale pominął CDN; stary host jest też zaszyty w czterech miejscach fixture'ów.

### Werdykty per endpoint

| endpoint | werdykt |
|---|---|
| `POST /{merchantId}/service/{serviceId}/get-payment-methods` | ROZBIEŻNOŚCI |
| `GET /{merchantId}/service/{serviceId}` | ROZBIEŻNOŚCI |
| `GET /{merchantId}/services` | ROZBIEŻNOŚCI |
| `GET /{merchantId}/settings/ips` | ROZBIEŻNOŚCI |
| `PUT /{merchantId}/settings/ips` | ROZBIEŻNOŚCI |

Żaden z pięciu endpointów nie jest NIEZAIMPLEMENTOWANY — wszystkie mają metodę `Api`, builder URL,
DTO żądania (gdzie wymagane) i DTO odpowiedzi. Wszystkie sklejone URL-e oraz metody HTTP są zgodne
ze specyfikacją; rozpakowanie kolekcji w `GET /services` i `POST /get-payment-methods` jest również
zaimplementowane poprawnie. Rozbieżności dotyczą typowania pól odpowiedzi, egzekwowania enumów
w żądaniach, obsługi kodów błędów oraz pokrycia testami.
