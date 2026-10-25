# Zwroty

Audyt zgodności `routegroup/laravel-imoje` ze specyfikacją ING Pay RESTful API v1.0.2
(zakres: `POST /{merchantId}/transaction/{transactionId}/refund`,
`POST /{merchantId}/transaction/{transactionId}/can-refund`, instrukcje zwrotów i multiwypłat,
pola zwrotowe w DTO notyfikacji).

Repo: `/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a`
(gałąź `agent/ing-pay-api-audit-5dd10a`, HEAD `b8fb4bf`).

## Bilans

| Etykieta | Liczba |
|---|---|
| `[BRAK]` | 0 |
| `[BŁĄD]` | 4 |
| `[ROZBIEŻNOŚĆ]` | 9 |
| `[NADMIAR]` | 3 |
| `[INFO]` | 13 |
| **razem** | **29** |

Żadne pole obecne w specyfikacji nie jest nieobecne w repo — komplet pól request/response obu
endpointów jest zaimplementowany. Problemy dotyczą **mechaniki serializacji** (`BaseDto`),
**obsługi kodów HTTP** i **nadpisywania wartości domyślnych**, a nie brakujących pól.

---

## 1. `POST /{merchantId}/transaction/{transactionId}/refund` — Utworzenie zwrotu

**Werdykt: ROZBIEŻNOŚCI** — komplet pól jest zaimplementowany, ale repo wysyła pole, którego
integrator nie ustawił (`sendRefundConfirmationEmail: false`), rozbija żądanie przy próbie
nadpisania `serviceId`/`type` i milcząco połyka większość kodów błędów HTTP.

Spec: `spec/api-endpoints/post-merchantid-transaction-transactionid-refund.json`,
schemat `components.schemas.refund` w `spec/api-components.json`.

### 1.1 Metoda HTTP i URL

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `$this->request()->post($url, ...)` — `src/Lib/Api.php:96` | ZGODNE |
| serwer (prod) | `https://api.pay.ing.pl/v1/merchant` | `Environment::apiUrl()` → `https://api.pay.ing.pl/v1` — `src/Types/Environment.php:15`, segment `merchant` doklejany w `src/Lib/Url.php:17` | ZGODNE |
| serwer (sandbox) | `https://api.sandbox.pay.ing.pl/v1/merchant` | `src/Types/Environment.php:16` | ZGODNE |
| ścieżka | `/{merchantId}/transaction/{transactionId}/refund` | `Url::createRefundUrl()` — `src/Lib/Url.php:57-64` (składa `createTransactionUrl()` + `{transactionId}` + `refund`) | ZGODNE |
| autoryzacja | HTTP Bearer | `->withToken($this->config->apiKey)` — `src/Lib/Api.php:230` | ZGODNE |

Sklejony URL: `https://api.pay.ing.pl/v1/merchant/{merchantId}/transaction/{transactionId}/refund` —
zgodny. Test regresyjny: `tests/Lib/UrlTest.php:33-35`.

### 1.2 Parametry ścieżki

| parametr | spec | repo | status |
|---|---|---|---|
| `merchantId` | `string`, required | `Config::$merchantId` wstrzykiwany w `src/Lib/Url.php:18` | ZGODNE |
| `transactionId` | `$ref: transactionId` — `string`, `maxLength: 36`, `format: uuid`, pattern UUID v4, required | `string $transactionId` — `src/Lib/Api.php:93`, bez walidacji formatu i bez `rawurlencode()` (`src/Lib/Url.php:61`) | ZGODNE (uwaga w 1.6, poz. R5) |

### 1.3 Request body — `components.schemas.refund`

Plik: `src/DTO/Api/RefundDto.php`.

| pole | spec | repo | status |
|---|---|---|---|
| `type` | `string`, `enum: ["refund"]`, **required** | cast `TransactionType::class` (`RefundDto.php:28`), domyślnie `TransactionType::REFUND` (`RefundDto.php:49`) | ZGODNE (enum repo jest nadzbiorem: `sale` + `refund`, `src/Types/TransactionType.php:9-10`) |
| `serviceId` | `$ref: serviceId` — `string`, `maxLength: 36`, UUID v4, **required** | ustawiany z `Config::$serviceId` (`RefundDto.php:50`), brak castu i walidacji | ZGODNE jako wartość domyślna; **BŁĄD przy nadpisaniu** — poz. B1 |
| `amount` | `$ref: amount` — `integer`, kwota w groszach, **required** | cast `'int'` (`RefundDto.php:27`) | ROZBIEŻNOŚĆ — poz. R1 |
| `title` | `$ref: title` — `string`, `maxLength: 255`, pattern `^[A-Za-z0-9#&_\-,.\\/ À-ˀ"']+$`, opcjonalne | wyłącznie `@property-read string\|null $title` (`RefundDto.php:17`) — brak castu, brak walidacji | ROZBIEŻNOŚĆ — poz. R2 |
| `sendRefundConfirmationEmail` | `boolean`, opcjonalne | cast `'bool'` (`RefundDto.php:29`) | **BŁĄD** — poz. B2 |
| `multipayout` | **nie występuje** w schemacie `refund` | nie występuje w `RefundDto` | ZGODNE — poz. I1 |

Pól nadmiarowych w `RefundDto` nie ma — zestaw `type`/`serviceId`/`amount`/`title`/`sendRefundConfirmationEmail`
pokrywa się 1:1 ze schematem.

### 1.4 Response 200 — `components.responses.refund-response-200`

Plik: `src/DTO/Responses/RefundResponseDto.php` → cast na `src/DTO/Casts/TransactionDto.php`.

| pole | spec | repo | status |
|---|---|---|---|
| `transaction` | `object` | cast `TransactionDto::class` — `RefundResponseDto.php:21` | ZGODNE |
| `transaction.id` | `transactionId` — UUID v4 `string(36)` | `'string'` — `TransactionDto.php:53` | ZGODNE |
| `transaction.type` | `string`, `enum: ["refund"]` | `TransactionType` — `TransactionDto.php:54` | ZGODNE (nadzbiór) |
| `transaction.status` | `status` — `new`,`pending`,`settled`,`authorized`,`rejected`,`cancelled`,`error` | `TransactionStatus` — `TransactionDto.php:55` | ZGODNE (nadzbiór, poz. N2) |
| `transaction.source` | `source` — `web`,`api` | `TransactionSource` — `TransactionDto.php:56` | ZGODNE (`src/Types/TransactionSource.php:9-10`) |
| `transaction.created` | `created` — `["integer","null"]` | `'int'` — `TransactionDto.php:57`, `allowNull = true` (`:50`) zachowuje `null` | ZGODNE (uwaga: poz. I4) |
| `transaction.modified` | `modified` — `["integer","null"]` | `'int'` — `TransactionDto.php:58` | ZGODNE (uwaga: poz. I4) |
| `transaction.serviceId` | `serviceId` — UUID v4 | `'string'` — `TransactionDto.php:59` | ZGODNE |
| `transaction.amount` | `amount` — `integer` | `'int'` — `TransactionDto.php:60` | ZGODNE |
| `transaction.currency` | `currency` — 10 walut ISO 4217 | `Currency` — `TransactionDto.php:61` | ZGODNE (nadzbiór, poz. N1) |
| `transaction.orderId` | `orderId` — `string(100)` | `'string'` — `TransactionDto.php:62` | ZGODNE |
| `transaction.paymentMethod` | `paymentMethod` — 8 wartości | `PaymentMethod` — `TransactionDto.php:63` | ZGODNE (nadzbiór, poz. I5) |
| `transaction.paymentMethodCode` | `paymentMethodCode` — 47 wartości | `PaymentMethodCode` — `TransactionDto.php:64` | ZGODNE (nadzbiór — wszystkie 47 wartości spec obecne, poz. I5) |
| `additionalProperties` (oba poziomy) | dozwolone, schemat walidacyjny ma je przyjmować | `BaseDto extends Fluent` zachowuje nieznane klucze — `src/DTO/BaseDto.php:12` | ZGODNE |

`refund-response-200` **nie** zawiera `fee`, `refunds`, `title`, `notificationUrl` ani `payment` —
`TransactionDto` jest wspólnym castem dla `GET /transaction/{id}` i jest szerszy (poz. I3).

### 1.5 Kody błędów

| kod | spec | repo (`Api::validateResponse`, `src/Lib/Api.php:236-249`) | status |
|---|---|---|---|
| `200` | `refund-response-200` | `$response->ok()` → `return` (`:240-242`) | ZGODNE |
| `422` | `refund-response-422` (`apiErrorResponse`: `code`, `message`, `instance`, `errors`) | `throw new ApiErrorException($response, $request)` (`:245`) | ZGODNE |
| `500` | tabela w `ing-pay-api-topics/05-kody-odpowiedzi-http.md` | `throw new ApiErrorException($response)` (`:246`) — bez body żądania | ZGODNE |
| `400`, `401`, `403`, `404`, `406`, `503`, `504` | tabela w `05-kody-odpowiedzi-http.md` | `default => null` (`:247`) — **brak wyjątku** | **BŁĄD** — poz. B3 |

Parsowanie `apiErrorResponse` (`src/Exceptions/ApiErrorException.php:25-30`) rozpakowuje kopertę i
hydratuje `ApiErrorResponseDto` (`code`, `message`, `instance`, `errors` —
`src/DTO/Responses/ApiErrorResponseDto.php:17-21`). Zgodne ze schematem `apiErrorResponse`.

### 1.6 Ustalenia

**[BŁĄD] B1 — `array_merge_recursive` rozbija nadpisanie `serviceId` i `type`**
`src/DTO/Api/RefundDto.php:48`. PHPDoc konstruktora (`RefundDto.php:36-38`) jawnie dopuszcza
podanie `type` i `serviceId` („Required but passed by default"), ale `array_merge_recursive`
dla kluczy tekstowych o wartościach skalarnych **scala je w tablicę**, zamiast nadpisać:
- `new RefundDto(['amount' => 100, 'serviceId' => 'inne-uuid'])` → `serviceId` staje się
  `['uuid-z-configu', 'inne-uuid']` i trafia do body jako tablica JSON → odpowiedź `422` ze spec
  (`refund-response-422`), bo `serviceId` ma być `string`.
- `new RefundDto(['amount' => 100, 'type' => TransactionType::REFUND])` → `type` staje się
  tablicą dwóch enumów, po czym `BaseDto::castAttribute()` (`src/DTO/BaseDto.php:58`) wywołuje
  `TransactionType::from($value)` z tablicą → `TypeError` (fatal) jeszcze przed wysłaniem żądania.

To uniemożliwia wykonanie zwrotu dla innego sklepu niż domyślny z konfiguracji. Ten sam wzorzec
występuje w `src/DTO/Api/CancelPaymentDto.php:39`, `src/DTO/Api/TransactionDto.php:97`,
`src/DTO/Api/PaymentDto.php:71`, `src/DTO/Api/ChargeProfileDto.php:58`,
`src/DTO/Paywall/TransactionDto.php:73`, `src/DTO/Paywall/OneClickDto.php:78` — czyli jest to
problem ogólnopakietowy, nie tylko zwrotowy.

**[BŁĄD] B2 — `sendRefundConfirmationEmail` jest wysyłane zawsze, z wartością `false`**
`src/DTO/Api/RefundDto.php:29` + `src/DTO/BaseDto.php:26-34`.
`RefundDto` nie ustawia `allowNull` (domyślnie `false` — `src/DTO/BaseDto.php:16`), więc pętla
`castAttributes()` **materializuje każdy klucz z `$casts`**, również ten nieprzekazany:
`$attributes['sendRefundConfirmationEmail'] = (bool) null` → `false` (`BaseDto.php:27`, `:65`).
Ponieważ `allowNull` jest `false`, nie wykonuje się `array_filter()` (`BaseDto.php:30`), a
`Utils::transformValues()` (`src/Lib/Utils.php:49-68`) nie usuwa wartości falsy. W efekcie
**każde** żądanie zwrotu zawiera `"sendRefundConfirmationEmail": false`.

Spec (`components.schemas.refund`) opisuje to pole jako opcjonalne i nie podaje wartości domyślnej
po stronie ING — repo nie daje możliwości pominięcia pola, więc jawnie wyłącza e-mail
potwierdzający dla płatnika, nawet gdy integrator w ogóle o tym polu nie wie. Wzorzec zgodny ze
spec stosują DTO z `allowNull = true` (np. `src/DTO/Api/PaymentDto.php:38`, pokryte testem
`tests/Lib/ApiSpecTest.php:21-38`) — `RefundDto` jako jedyne z DTO zwrotowych go nie używa
(pełna lista: `grep -rn allowNull src/`).

**[BŁĄD] B3 — `Api::validateResponse` nie obsługuje kodów 400/401/403/404/406/503/504**
`src/Lib/Api.php:244-248` (`default => null`). Specyfikacja (`ing-pay-api-topics/05-kody-odpowiedzi-http.md`)
wylicza dla API osiem kodów błędów; repo reaguje na dwa. Dla `createRefund()` oznacza to, że po
np. `404` (zły `transactionId`), `403` (brak uprawnień), `401` (wygasły token) albo `503`/`504`
metoda **nie rzuca wyjątku**, tylko kontynuuje do `new RefundResponseDto($response)`
(`src/Lib/Api.php:99`). Ciało błędu nie zawiera klucza `transaction`, więc
`BaseDto::castAttribute()` (`src/DTO/BaseDto.php:43-47`) tworzy `new TransactionDto([])`;
`TransactionDto` ma `allowNull = true` (`src/DTO/Casts/TransactionDto.php:50`), więc wszystkie
pola są `null` i filtrowane. Wywołujący dostaje pozornie poprawny `RefundResponseDto`
z pustą transakcją — **nieudany zwrot wygląda jak udany**. Brak testu dla tej ścieżki
(`tests/Lib/ApiTest.php:64-70` sprawdza wyłącznie happy path).

**[ROZBIEŻNOŚĆ] R1 — brak `amount` daje `amount: 0` zamiast braku pola**
`src/DTO/Api/RefundDto.php:27` + `src/DTO/BaseDto.php:27`. `amount` jest w spec `required`;
pominięcie go lokalnie nie generuje błędu, tylko cichy `(int) null === 0` w body. Zamiast
czytelnego błędu lokalnego integrator dostaje `422` z serwera ING. Ten sam mechanizm maskuje
literówkę w nazwie klucza (`Amount` → dodatkowy klucz + `amount: 0`).

**[ROZBIEŻNOŚĆ] R2 — brak walidacji `title` (maxLength 255 i pattern znaków)**
Spec `components.schemas.title`: `maxLength: 255`, pattern `^[A-Za-z0-9#&_\-,.\\/ À-ˀ"']+$`.
`RefundDto` nie ma `title` w `$casts` (`src/DTO/Api/RefundDto.php:26-30`) ani żadnej walidacji —
tytuł z niedozwolonymi znakami (np. `;`, `:`, `(`, `)`, `+`) przechodzi do żądania i kończy się
`422` z serwera. `src/Lib/Validator.php` waliduje wyłącznie notyfikacje przychodzące i nie jest
używany nigdzie w `src/` poza samym sobą.

**[ROZBIEŻNOŚĆ] R5 — `transactionId` i `merchantId` nie są kodowane w URL**
`src/Lib/Url.php:57-64` skleja segmenty przez `implode('/')` bez `rawurlencode()`. Spec wymusza
pattern UUID v4 (`components.schemas.transactionId`), więc w praktyce ryzyko jest niskie, ale
wartość spoza wzorca (np. zawierająca `/` lub `?`) cicho zmienia ścieżkę żądania. Brak też
lokalnej walidacji wzorca UUID.

**[ROZBIEŻNOŚĆ] R6 — `ApiErrorException` zawsze ustawia kod wyjątku `422`**
`src/Exceptions/ApiErrorException.php:33`: `parent::__construct($this->dto->message, 422)` —
również dla odpowiedzi `500` (`src/Lib/Api.php:246`). Kod wyjątku przestaje odwzorowywać status
HTTP, a spec traktuje 422 i 500 jako jakościowo różne sytuacje
(`05-kody-odpowiedzi-http.md`: 422 = błąd payloadu po stronie integratora, 500 = awaria dostawcy,
z zaleceniem czasowego wyłączenia kanału płatności).

**[NADMIAR] N1 — `Currency::UAH` i `Currency::HRK` poza enumem specyfikacji**
`src/Types/Currency.php:14` (`UAH`), `src/Types/Currency.php:15` (`HRK`).
`components.schemas.currency` dopuszcza wyłącznie `PLN, EUR, USD, GBP, CHF, SEK, HUF, CZK, BGN, RON`.
Dla odpowiedzi zwrotu enum repo jest nadzbiorem, więc parsowanie jest bezpieczne; wartości te są
natomiast nieosiągalne po stronie ING.

**[NADMIAR] N2 — `TransactionStatus::SUBMITTED_FOR_SETTLEMENT` i `TransactionStatus::REFUND` poza enumem specyfikacji**
`src/Types/TransactionStatus.php:12`, `src/Types/TransactionStatus.php:17`.
`components.schemas.status` dopuszcza `new, pending, settled, authorized, rejected, cancelled, error`.
Dodatkowo `TransactionStatus::canChange()` (`src/Types/TransactionStatus.php:19-46`) modeluje
`settled → refund` jako przejście statusu transakcji, podczas gdy w specyfikacji zwrot to osobna
transakcja (`type: refund`), a nie status transakcji sprzedaży — model domenowy repo odbiega
od modelu ING.

**[INFO] I1 — brak `multipayout` w `RefundDto` jest zgodny ze specyfikacją**
Przeszukanie `spec/api-components.json` pokazuje, że `multipayout` występuje wyłącznie w schematach
`transaction`, `payment` i `debit-profile` — **nie** w `refund` ani `can-refund`. Repo poprawnie go
nie wysyła przy zwrocie. Szerzej: sekcja „Instrukcje" poniżej.

**[INFO] I2 — odpowiedź zwrotu nie zawiera `title`**
Schemat `refund-response-200` nie ma `title` (w przeciwieństwie do `get-transaction-response-200`),
więc `$response->transaction->title` będzie zawsze `null` mimo że `title` można przesłać w żądaniu.

**[INFO] I3 — `TransactionDto` jest szerszy niż kontrakt `refund-response-200`**
`src/DTO/Casts/TransactionDto.php:24,32-42` deklaruje `notificationUrl`, `payment`,
`paymentProfile`, `refunds`, `blikTransactionId`, `statusCode`, `statusCodeDescription`, `fee`,
`payerData`, `payerCardData`, `paidAmount`, `action` — pola z `get-transaction-response-200`,
nieobecne w odpowiedzi zwrotu. Spec dopuszcza `additionalProperties`, więc nie jest to błąd,
ale typ zwracany przez `createRefund()` sugeruje dostępność pól, których ING tu nie przysyła.

**[INFO] I4 — `allowNull` w `TransactionDto` zeruje wartości falsy**
`src/DTO/BaseDto.php:39` używa `empty($value)`, a `:30-32` wykonuje `array_filter()`. Dla
`TransactionDto` (`allowNull = true`) oznacza to, że legalne `0` / `""` / `false` w odpowiedzi
staje się `null` i znika z `toArray()`. W odpowiedzi zwrotu `amount` i `created` nigdy nie będą `0`,
więc ryzyko praktyczne jest niskie, ale mechanika jest szersza niż „null → null".

**[INFO] I5 — enumy metod płatności są nadzbiorem specyfikacji (bezpieczne parsowanie)**
`src/Types/PaymentMethodCode.php` zawiera wszystkie 47 wartości ze spec plus 7 własnych
(`blik_oneclick`, `c2p`, `citi`, `envelo`, `ideabank`, `imoje_installments`, `tmobile`);
`src/Types/PaymentMethod.php:13,17` dodaje `lease` i `visa_mobile` ponad 8 wartości spec.
Żadna wartość ze spec nie brakuje, więc `::from()` nie rzuci `ValueError` przy hydratacji
odpowiedzi zwrotu.

**[INFO] I6 — brak testów ciała żądania i ścieżek błędów dla zwrotu**
`tests/Lib/ApiTest.php:64-70` sprawdza wyłącznie, że `createRefund()` zwraca instancję
`RefundResponseDto`; nie asercjonuje wysłanego body (nie wykryje B2 ani B1) ani odpowiedzi 422/500/404.
`tests/Lib/ApiSpecTest.php` (112 linii) nie zawiera ani jednego przypadku zwrotowego — testy
zgodności ze spec pokrywają tylko `payment` i `transaction`.

**[INFO] I7 — wymóg min. 5 s odstępu między zwrotami nie jest nigdzie odnotowany w repo**
Spec powtarza go dwukrotnie (opis operacji `refund` oraz `ing-pay-paywall-topics/06-zwroty.md`).
Repo nie wymusza ani nie dokumentuje żadnego throttlingu — przy masowych zwrotach odpowiedzialność
spada w całości na integratora, bez ostrzeżenia w API pakietu.

---

## 2. `POST /{merchantId}/transaction/{transactionId}/can-refund` — Informacja o możliwości zwrotu

**Werdykt: ROZBIEŻNOŚCI** — wszystkie pola żądania i odpowiedzi są zaimplementowane poprawnie,
ale brak `allowNull` w `CanRefundResponseDto` sprawia, że nieobsłużone kody HTTP dają poprawnie
wyglądający obiekt z `refundable = false`.

Spec: `spec/api-endpoints/post-merchantid-transaction-transactionid-can-refund.json`,
schematy `components.schemas.can-refund` i `components.responses.can-refund-response-200`.

### 2.1 Metoda HTTP i URL

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `src/Lib/Api.php:173` | ZGODNE |
| ścieżka | `/{merchantId}/transaction/{transactionId}/can-refund` | `Url::createCanRefundUrl()` — `src/Lib/Url.php:104-111` | ZGODNE |

Sklejony URL: `https://api.pay.ing.pl/v1/merchant/{merchantId}/transaction/{transactionId}/can-refund`.
Test: `tests/Lib/UrlTest.php:53-55`.

### 2.2 Parametry ścieżki

Identycznie jak w 1.2 — `merchantId` z konfiguracji (`src/Lib/Url.php:18`),
`transactionId` jako argument `src/Lib/Api.php:170`, bez walidacji UUID (poz. R5).

### 2.3 Request body — `components.schemas.can-refund`

Plik: `src/DTO/Api/CanRefundDto.php`.

| pole | spec | repo | status |
|---|---|---|---|
| `amount` | `$ref: amount` — `integer`, **required** (jedyne pole schematu) | cast `'int'` — `CanRefundDto.php:21` | ROZBIEŻNOŚĆ — poz. R3 |

Pól nadmiarowych brak — `CanRefundDto` zawiera wyłącznie `amount`. Poprawnie **nie** dokleja
`serviceId` ani `type` (schemat `can-refund` ich nie definiuje), w odróżnieniu od `RefundDto`.

### 2.4 Response 200 — `components.responses.can-refund-response-200`

Pliki: `src/DTO/Responses/CanRefundResponseDto.php`, `src/DTO/Responses/PartialRefundDto.php`.

| pole | spec | repo | status |
|---|---|---|---|
| `id` | `$ref: transactionId` — UUID v4 `string(36)` | `'string'` — `CanRefundResponseDto.php:24` | ZGODNE |
| `refundable` | `boolean` — czy transakcja może zostać zwrócona | `'bool'` — `CanRefundResponseDto.php:25` | ZGODNE (brak w body → `false`, poz. B4) |
| `balance` | `boolean` — czy saldo sklepu wystarcza | `'bool'` — `CanRefundResponseDto.php:26` | ZGODNE (brak w body → `false`, poz. B4) |
| `fullRefund` | `integer` — wartość pełnej kwoty zwrotu | `'int'` — `CanRefundResponseDto.php:27` | ZGODNE (brak w body → `0`, poz. B4) |
| `partialRefund` | `object`, wszystkie pola opcjonalne | cast `PartialRefundDto::class` — `CanRefundResponseDto.php:28` | ROZBIEŻNOŚĆ — poz. R4 |
| `partialRefund.maxRefundAmount` | `integer` | `'int'` — `PartialRefundDto.php:16` | ZGODNE |
| `partialRefund.minRefundAmount` | `integer` | `'int'` — `PartialRefundDto.php:17` | ZGODNE |
| `additionalProperties` (oba poziomy) | dozwolone | `Fluent` zachowuje nieznane klucze | ZGODNE |

Schemat `can-refund-response-200` **nie definiuje listy `required`** — wszystkie pola odpowiedzi są
formalnie opcjonalne. `PartialRefundDto` nie jest pokryte fabryką (brak
`src/Factories/Responses/PartialRefundDtoFactory.php`), jest hydratowane inline przez
`src/Factories/Responses/CanRefundResponseDtoFactory.php:23-26`.

### 2.5 Kody błędów

| kod | spec | repo | status |
|---|---|---|---|
| `200` | `can-refund-response-200` | `src/Lib/Api.php:240-242` | ZGODNE |
| pozostałe | endpoint **nie deklaruje żadnej odpowiedzi błędu**; obowiązuje globalna tabela `05-kody-odpowiedzi-http.md` | `422`/`500` → wyjątek, reszta → `default => null` (`src/Lib/Api.php:244-248`) | **BŁĄD** — poz. B4 |

### 2.6 Ustalenia

**[BŁĄD] B4 — nieobsłużony kod HTTP daje `refundable = false` zamiast wyjątku**
`src/DTO/Responses/CanRefundResponseDto.php:23-29` nie ustawia `allowNull`, więc dziedziczy
`false` z `src/DTO/BaseDto.php:16`. W połączeniu z luką w `Api::validateResponse`
(`src/Lib/Api.php:247`, poz. B3) odpowiedź `403`/`404`/`503`/`504` przechodzi do
`new CanRefundResponseDto($response)` (`src/Lib/Api.php:176`), a `castAttributes()`
(`src/DTO/BaseDto.php:26-28`) wypełnia brakujące klucze wartościami domyślnymi:

```
id           => (string) null => ''
refundable   => (bool) null   => false
balance      => (bool) null   => false
fullRefund   => (int) null    => 0
partialRefund => new PartialRefundDto([]) => maxRefundAmount: 0, minRefundAmount: 0
```

Integrator sprawdzający `if (! $response->refundable)` zinterpretuje awarię sieci lub wygaśnięcie
tokenu jako „zwrot niedozwolony". Jest to kierunek fail-safe (nie wykona się zwrot, który nie
powinien), ale **nierozróżnialny od poprawnej odpowiedzi** `refundable: false` — nie da się
odróżnić decyzji ING od błędu transportowego, a `fullRefund: 0` / `minRefundAmount: 0` mogą zostać
użyte jako realne limity. Brak testu dla tej ścieżki (`tests/Lib/ApiTest.php:134-140` pokrywa
wyłącznie `200`).

**[ROZBIEŻNOŚĆ] R3 — brak `amount` w `CanRefundDto` daje `amount: 0`**
`src/DTO/Api/CanRefundDto.php:21` + `src/DTO/BaseDto.php:27`. Identyczny mechanizm jak R1:
`amount` jest w spec `required`, a repo wysyła ciche `0` zamiast zgłosić brak lokalnie.

**[ROZBIEŻNOŚĆ] R4 — `partialRefund` nigdy nie jest `null` wbrew deklaracji PHPDoc**
`src/DTO/Responses/CanRefundResponseDto.php:15` deklaruje `@property-read PartialRefundDto|null $partialRefund`,
ale cast w `:28` przechodzi przez `src/DTO/BaseDto.php:43-47`, który dla klasy potomnej `BaseDto`
zawsze robi `new $castType($value ?? [])`. Przy braku `partialRefund` w odpowiedzi (spec dopuszcza —
brak `required`) obiekt istnieje, a `maxRefundAmount`/`minRefundAmount` wynoszą `0`
(`src/DTO/Responses/PartialRefundDto.php:15-18`, również bez `allowNull`). Sprawdzenie
`if ($response->partialRefund !== null)` nigdy nie zadziała, a `0` jako maksymalna kwota zwrotu
częściowego jest nieodróżnialne od braku danych.

**[INFO] I8 — specyfikacja nie deklaruje dla `can-refund` żadnego kodu błędu**
Operacja ma w `responses` wyłącznie `200` — w odróżnieniu od `refund`, które deklaruje `422`.
Obsługa błędów opiera się więc wyłącznie na globalnej tabeli kodów HTTP; repo nie dokumentuje
tej asymetrii.

**[INFO] I9 — niespójna kolejność argumentów w `Api`**
`Api::canRefund(CanRefundDto $dto, string $transactionId)` (`src/Lib/Api.php:168-171`) i
`Api::createRefund(RefundDto $dto, string $transactionId)` (`src/Lib/Api.php:91-94`) przyjmują
parametr ścieżki jako drugi argument, podczas gdy
`Api::getPaymentMethods(string $serviceId, GetPaymentMethodsDto $dto)` (`src/Lib/Api.php:179-182`)
odwrotnie. Nie jest to niezgodność ze spec, ale pułapka ergonomiczna przy podobnych sygnaturach.

---

## 3. Instrukcje (multiwypłaty, zwroty częściowe)

Źródła: `spec/ing-pay-paywall-topics/06-zwroty.md`, `spec/ing-pay-api-topics/10-multiwyplaty.md`,
`spec/ing-pay-paywall-topics/12-multiwyplaty.md`, opis operacji `refund`.

### 3.1 Multiwypłaty a zwroty

| ustalenie | spec | repo |
|---|---|---|
| `multipayout` w zwrocie | schemat `refund` **nie zawiera** `multipayout`; pole występuje wyłącznie w `transaction`, `payment` i `debit-profile` (`spec/api-components.json`) | `RefundDto` go nie ma — ZGODNE |
| `multipayout` w `can-refund` | schemat `can-refund` ma wyłącznie `amount` | `CanRefundDto` go nie ma — ZGODNE |
| struktura elementu | `ban` (string, pattern `^[0-9]{26}$`, min/max 26), `amount` (integer), `label` (string, max 70, pattern znaków) — wszystkie `required`; `title` (string, max 105, pattern) opcjonalne | `src/DTO/Api/TransactionDto.php:41,89` — poz. R7 |

**[INFO] I10 — specyfikacja nie przewiduje zwrotów z rozbiciem na multiwypłaty**
Ani schemat `refund`, ani instrukcje `10-multiwyplaty.md` / `12-multiwyplaty.md` nie opisują
mechanizmu zwracania środków na poszczególne rachunki z `multipayout`. Instrukcje odsyłają wyłącznie
do multiwypłat w transakcji i w linku płatności. Zwrot jest zawsze operacją na całej transakcji
(`amount` + opcjonalny `title`), więc brak `multipayout` w `RefundDto` jest zgodny — nie jest to `[BRAK]`.

**[ROZBIEŻNOŚĆ] R7 — `multipayout` w `Api\TransactionDto` bez DTO i bez walidacji**
`src/DTO/Api/TransactionDto.php:41` (`@property-read array $multipayout`),
`src/DTO/Api/TransactionDto.php:63` (zakomentowany cast: `// 'multipayout' => 'array' @todo`),
`src/DTO/Api/TransactionDto.php:89` (`multipayout?: array<mixed>`).
Tablica przechodzi jako surowy `array<mixed>`: brak egzekwowania `ban` = dokładnie 26 cyfr,
`label` ≤ 70 znaków z ograniczonym zestawem znaków, `title` ≤ 105 znaków, brak wymuszenia
reguły „podanie `title` dla jednego elementu wymusza go dla pozostałych"
(`12-multiwyplaty.md`) i brak sprawdzenia, że wszystkie wymagane klucze są obecne.
Dotyczy endpointu `POST /{merchantId}/transaction` (zakres innego audytora), odnotowane tu,
bo instrukcja multiwypłat jest częścią mojego zakresu. `src/DTO/Paywall/TransactionDto.php` w ogóle
nie wspiera `multipayout`, mimo że `12-multiwyplaty.md` opisuje go głównie jako pola formularza
paywalla (`multipayout[0][ban]` …) wliczane do sygnatury.

### 3.2 Zwroty częściowe

| ustalenie | spec | repo |
|---|---|---|
| mechanizm | `refund.amount` < pełna kwota transakcji; granice z `can-refund` → `partialRefund.minRefundAmount` / `maxRefundAmount` | `RefundDto::$amount` (`src/DTO/Api/RefundDto.php:27`) + `PartialRefundDto` (`src/DTO/Responses/PartialRefundDto.php`) — pola obecne, zero logiki łączącej |
| raty (`imoje_installments`) | `ing-pay-paywall-topics/10-raty.md:117-121` — dostawca decyduje o wysokości zwrotu, trzeba użyć `can-refund` | brak wzmianki w repo |

**[INFO] I11 — brak lokalnej kontroli zakresu kwoty zwrotu częściowego**
Repo nie wiąże wyniku `canRefund()` z `createRefund()` — nie ma pomocniczej metody ani walidacji
`minRefundAmount ≤ amount ≤ maxRefundAmount`. Spec pokazuje, że przekroczenie limitu kończy się
`422` z kodem `TRX-ERROR-120100` („Refund amount too large.", przykład w `refund-response-422`).
Kod jest dostępny przez `$exception->dto->code` (`src/DTO/Responses/ApiErrorResponseDto.php:18`),
ale repo nie ma stałych ani enuma dla kodów biznesowych. Specyfikacja również nie publikuje
zamkniętej listy kodów `TRX-ERROR-*` — w całym materiale występują tylko cztery jako przykłady
(`TRX-ERROR-120001`, `-120003`, `-120100`, `-120301`), więc generyczne `code`/`message` jest
rozwiązaniem adekwatnym.

**[INFO] I12 — specyfikacja jest wewnętrznie niespójna co do okien czasowych zwrotu**
Opis operacji `refund`: do **15 miesięcy** od utworzenia transakcji, wyjątek BLIK / Twisto / PayPal —
**12 miesięcy**. `ing-pay-paywall-topics/06-zwroty.md`: BLIK — 12 miesięcy, płatności odroczone —
12 miesięcy, **karty płatnicze — 3 lata**. Obie instrukcje opisują te same operacje i podają różne
limity (zwłaszcza dla kart: 15 miesięcy vs 3 lata). Repo nie implementuje żadnego z wariantów —
to walidacja zdalna, więc nie jest to wada kodu, ale rozbieżność warto odnotować przed
ewentualnym dodaniem logiki lokalnej.

**[ROZBIEŻNOŚĆ] R8 — `docs/api.md` nie opisuje zwrotów i wskazuje nieaktualną dokumentację**
`docs/api.md` (6 linii) odsyła do `imojeapi.docs.apiary.io` / `imojeapieng.docs.apiary.io`, podczas
gdy źródłem prawdy jest `bump.sh/pgw/doc/ing-pay-api` (migracja hostów do `pay.ing.pl` weszła
commitem `b6821f4`). `docs/paywall.md:40-42` poprawnie kieruje zwroty do API, ale `docs/api.md`
nie dokumentuje ani `createRefund()`, ani `canRefund()`. Zgodnie z `AGENTS.md` pliki w `docs/` są
dokumentacją konsumencką — zwroty, jedyna operacja niedostępna z paywalla, nie mają tam opisu.

---

## 4. Notyfikacje zwrotowe (wyłącznie w zakresie DTO zwrotu)

Pełny audyt notyfikacji prowadzi inny agent; poniżej tylko to, co dotyczy DTO zwrotowych.
Źródło: `spec/ing-pay-api-topics/06-notyfikacje.md:312-316`.

| pole | spec | repo | status |
|---|---|---|---|
| `transaction.type` | `string`, dopuszczalne `sale`, `refund` (`06-notyfikacje.md:50`) | `TransactionType` w `src/DTO/Casts/TransactionDto.php:54` | ZGODNE |
| `transaction` | obiekt notyfikacji | cast `TransactionDto::class` — `src/DTO/Notifications/RefundNotificationDto.php:27` | ZGODNE |
| `payment` | obiekt notyfikacji | cast `TransactionPaymentDto::class` — `RefundNotificationDto.php:28` | ZGODNE |
| `originalTransactionId` | **brak w całej specyfikacji ING Pay** | `RefundNotificationDto.php:16,29` | NADMIAR — poz. N3 |
| `transaction.refunds` | `get-transaction-response-200` → tablica obiektów `{transaction: {...}}` | `src/DTO/Casts/TransactionDto.php:34` — tylko PHPDoc, brak castu | ROZBIEŻNOŚĆ — poz. R9 |
| `transaction.fee` | `{merchant: integer, surcharge: integer}` | `src/DTO/Casts/FeeDto.php:17-20` (`merchant`, `surcharge`, `allowNull = true`) | ZGODNE |

**[NADMIAR] N3 — `RefundNotificationDto::$originalTransactionId` nie występuje w specyfikacji**
`src/DTO/Notifications/RefundNotificationDto.php:16` (PHPDoc) i `:29` (cast `'string'`), fabryka
`src/Factories/Notifications/RefundNotificationDtoFactory.php:34`. Przeszukanie całego katalogu
`spec/` (OpenAPI + wszystkie rozdziały instrukcji API i Paywall) nie zwraca ani jednego wystąpienia
`originalTransactionId`. Sekcja „Notyfikacje do zwrotów" (`06-notyfikacje.md:312-316`) nie opisuje
osobnego payloadu — stwierdza tylko, że notyfikacje zwrotowe są **domyślnie wyłączone** i trzeba je
włączyć mailowo (tech@pay.ing.pl). Pole jest najprawdopodobniej pozostałością po starszej
dokumentacji imoje; w obecnej specyfikacji ING Pay nie ma dla niego pokrycia i nie można
zagwarantować, że ING je przysyła. `RefundNotificationDto` ma `allowNull = true` (`:24`), więc przy
braku pola będzie `null` — czyli brak awarii, ale też brak gwarancji kontraktu.

**[ROZBIEŻNOŚĆ] R9 — `transaction.refunds` nie jest hydratowane do DTO**
`src/DTO/Casts/TransactionDto.php:34` deklaruje `@property-read array $refunds`, ale `refunds` nie
występuje w `$casts` (`:52-70`). Zgodnie ze spec (`get-transaction-response-200` →
`transaction.refunds`) jest to tablica obiektów o kształcie `{ transaction: { id, type, status,
source, created, modified, serviceId, amount, currency, title, orderId, paymentMethod,
paymentMethodCode } }`. Konsument dostaje surowe tablice zamiast `TransactionDto` — enumy nie są
hydratowane, kwoty pozostają takie, jak przyszły w JSON-ie, a dostęp wygląda inaczej
(`$t->refunds[0]['transaction']['amount']`) niż dla pozostałych zagnieżdżeń w tym samym DTO.
Jest to jedyne miejsce, w którym lista zwrotów do transakcji jest dostępna, więc mimo że pole
należy do `GET /transaction/{id}`, dotyczy domeny zwrotów.

**[INFO] I13 — notyfikacje zwrotowe są domyślnie wyłączone po stronie ING**
`06-notyfikacje.md:314-316` (identycznie `ing-pay-paywall-topics/05-notyfikacje.md:314-316`):
„Domyślnie nie wysyłamy notyfikacji do zwrotów" — trzeba o nie poprosić mailowo, podając
identyfikator sklepu. Repo dostarcza `RefundNotificationDto` i walidację podpisu
(`src/Lib/Validator.php:26`), ale nigdzie nie sygnalizuje, że bez włączenia po stronie ING
notyfikacja nigdy nie dotrze — integrator może szukać błędu u siebie.

---

## 5. Zestawienie ustaleń

### `[BŁĄD]` (4)

| ID | Opis | Lokalizacja w repo | Miejsce w spec |
|---|---|---|---|
| B1 | `array_merge_recursive` scala, zamiast nadpisywać — nadpisanie `serviceId` daje tablicę (→ 422), nadpisanie `type` daje `TypeError` | `src/DTO/Api/RefundDto.php:48`, `src/DTO/BaseDto.php:58` | `components.schemas.refund` (`serviceId`, `type` jako `string`) |
| B2 | `sendRefundConfirmationEmail` wysyłane w **każdym** żądaniu jako `false`, bez możliwości pominięcia | `src/DTO/Api/RefundDto.php:29`, `src/DTO/BaseDto.php:16,27,30` | `components.schemas.refund.properties.sendRefundConfirmationEmail` (opcjonalne) |
| B3 | `validateResponse` ignoruje 400/401/403/404/406/503/504 → pusty `RefundResponseDto` zamiast wyjątku | `src/Lib/Api.php:244-248`, `src/Lib/Api.php:99` | `ing-pay-api-topics/05-kody-odpowiedzi-http.md` |
| B4 | `CanRefundResponseDto` bez `allowNull` → nieobsłużony kod HTTP daje `refundable=false, balance=false, fullRefund=0` | `src/DTO/Responses/CanRefundResponseDto.php:23-29`, `src/Lib/Api.php:176` | `components.responses.can-refund-response-200` (brak `required`) |

### `[ROZBIEŻNOŚĆ]` (9)

| ID | Opis | Lokalizacja w repo | Miejsce w spec |
|---|---|---|---|
| R1 | Brak `amount` → `amount: 0` w body zwrotu zamiast błędu lokalnego | `src/DTO/Api/RefundDto.php:27`, `src/DTO/BaseDto.php:27` | `components.schemas.refund.required: ["type","serviceId","amount"]` |
| R2 | Brak walidacji `title` (`maxLength: 255`, pattern znaków) | `src/DTO/Api/RefundDto.php:17,26-30` | `components.schemas.title` |
| R3 | Brak `amount` → `amount: 0` w `can-refund` | `src/DTO/Api/CanRefundDto.php:21` | `components.schemas.can-refund.required: ["amount"]` |
| R4 | `partialRefund` nigdy nie jest `null` wbrew PHPDoc; przy braku w body daje `0`/`0` | `src/DTO/Responses/CanRefundResponseDto.php:15,28`, `src/DTO/Responses/PartialRefundDto.php:15-18`, `src/DTO/BaseDto.php:43-47` | `can-refund-response-200.partialRefund` |
| R5 | `transactionId`/`merchantId` nie są kodowane w URL, brak walidacji UUID v4 | `src/Lib/Url.php:57-64,104-111` | `components.schemas.transactionId` (pattern UUID v4) |
| R6 | `ApiErrorException` zawsze ustawia kod wyjątku `422`, także dla `500` | `src/Exceptions/ApiErrorException.php:33` | `05-kody-odpowiedzi-http.md` (422 vs 500 to różne scenariusze) |
| R7 | `multipayout` jako surowy `array<mixed>`, bez DTO i walidacji (`ban` 26 cyfr, `label` 70, `title` 105, reguła „wszystko albo nic" dla `title`) | `src/DTO/Api/TransactionDto.php:41,63,89` | `components.schemas.multipayout`, `ing-pay-paywall-topics/12-multiwyplaty.md` |
| R8 | `docs/api.md` nie opisuje zwrotów i linkuje do nieaktualnej dokumentacji imoje (apiary) | `docs/api.md:4-5` | `bump.sh/pgw/doc/ing-pay-api` jako źródło prawdy |
| R9 | `transaction.refunds` nie jest hydratowane do DTO — surowe tablice | `src/DTO/Casts/TransactionDto.php:34,52-70` | `get-transaction-response-200.transaction.refunds` |

> R9 opisane szczegółowo w sekcji 4 (notyfikacje zwrotowe).

### `[NADMIAR]` (3)

| ID | Opis | Lokalizacja w repo | Miejsce w spec |
|---|---|---|---|
| N1 | `Currency::UAH`, `Currency::HRK` poza enumem waluty | `src/Types/Currency.php:14,15` | `components.schemas.currency` (10 walut) |
| N2 | `TransactionStatus::SUBMITTED_FOR_SETTLEMENT`, `TransactionStatus::REFUND` poza enumem statusu; `canChange()` modeluje zwrot jako status, nie jako osobną transakcję | `src/Types/TransactionStatus.php:12,17,19-46` | `components.schemas.status` (7 statusów) |
| N3 | `RefundNotificationDto::$originalTransactionId` — brak w całej specyfikacji ING Pay | `src/DTO/Notifications/RefundNotificationDto.php:16,29` | brak odpowiednika (`06-notyfikacje.md:312-316`) |

### `[BRAK]` (0)

Żadne pole ze specyfikacji nie jest nieobecne w repo. Zweryfikowano komplet:
`refund` (5 pól), `can-refund` (1 pole), `refund-response-200` (12 pól `transaction`),
`can-refund-response-200` (4 pola + 2 zagnieżdżone), `apiErrorResponse` (4 pola).

### `[INFO]` (13)

I1 brak `multipayout` w `refund` jest zgodny ze spec · I2 odpowiedź zwrotu nie zawiera `title` ·
I3 `TransactionDto` szerszy niż kontrakt `refund-response-200` · I4 `allowNull` zeruje wartości
falsy (`empty()` + `array_filter`) · I5 enumy metod płatności są bezpiecznym nadzbiorem ·
I6 brak testów ciała żądania i ścieżek błędów dla zwrotów · I7 wymóg 5 s odstępu nieodnotowany ·
I8 spec nie deklaruje kodów błędu dla `can-refund` · I9 niespójna kolejność argumentów w `Api` ·
I10 spec nie przewiduje zwrotów z rozbiciem na multiwypłaty · I11 brak lokalnej kontroli zakresu
zwrotu częściowego i katalogu kodów `TRX-ERROR-*` · I12 spec wewnętrznie niespójna co do okien
czasowych zwrotu · I13 notyfikacje zwrotowe domyślnie wyłączone po stronie ING.
