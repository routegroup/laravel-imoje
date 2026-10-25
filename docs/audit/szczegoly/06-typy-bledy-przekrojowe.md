# Warstwa przekrojowa: typy, enumy, kody błędów, środowiska

Zakres: `src/Types/*`, `src/Lib/Api.php`, `src/Lib/Url.php`, `src/Lib/Utils.php`, `src/Lib/Validator.php`,
`src/DTO/Responses/ApiErrorResponseDto.php`, `src/Exceptions/*`.
Źródło prawdy: `spec/api-components.json`, `spec/api-endpoints/*.json`, `spec/ing-pay-api.json`,
`spec/ing-pay-paywall.json`, rozdziały `ing-pay-api-topics/{00,01,02,03,04,05,09,11,12,13,14,15,16}.md`
oraz `ing-pay-paywall-topics/{02,03,08,15}.md`.

Ścieżki repo podawane względem
`/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a`.

Podsumowanie ilościowe: `[BŁĄD]` 8 · `[BRAK]` 7 · `[NADMIAR]` 6 · `[ROZBIEŻNOŚĆ]` 9 · `[INFO]` 6.

---

## 1. Enumy — porównanie dwukierunkowe

### 1.1 `ActionType` — zgodny

`src/Types/ActionType.php:7-8` vs `api-components.json` → `schemas.action.properties.type.enum`.

| wartość | spec | repo |
|---|---|---|
| `redirect` | tak | `ActionType::REDIRECT` (l. 7) |
| `transfer` | tak | `ActionType::TRANSFER` (l. 8) |

`[INFO]` Pełna zgodność w obie strony. Uwaga poboczna: `schemas.action.properties.method.enum`
(`GET`, `POST`) nie ma odpowiednika typowanego — `src/DTO/Casts/ActionDto.php:31` trzyma `method`
jako `'string'`. `[ROZBIEŻNOŚĆ]` — brak enuma dla `action.method`.

### 1.2 `Currency` — 2 nadmiarowe waluty

`src/Types/Currency.php:9-20` vs `api-components.json` → `schemas.currency.enum`
(`PLN, EUR, USD, GBP, CHF, SEK, HUF, CZK, BGN, RON` — 10 wartości).

| w spec, brak w repo | w repo, brak w spec |
|---|---|
| — | `UAH` (`src/Types/Currency.php:14`), `HRK` (`src/Types/Currency.php:15`) |

`[NADMIAR]` `Currency::UAH` i `Currency::HRK` nie istnieją w `schemas.currency.enum`. `HRK` jest
walutą wycofaną (Chorwacja w strefie euro od 2023). Transakcja utworzona z którąkolwiek z tych
wartości zostanie odrzucona przez API z kodem 422 — biblioteka nie zasygnalizuje tego wcześniej,
bo `TransactionDto` castuje `currency` przez `Currency::from()` bez weryfikacji dostępności
(`src/DTO/Api/TransactionDto.php:55`).

`[INFO]` Wszystkie 10 walut ze specyfikacji jest obecnych w repo. Dostępność waluty jest jednak
zawężona per metoda/kanał (`ing-pay-api-topics/03-metody-i-kanaly-realizacji-transakcji.md`,
np. BLIK/ING/`wt`/`imoje_paylater`/`imoje_installments` — wyłącznie `PLN`); repo nie modeluje tej
zależności w żaden sposób.

### 1.3 `Lang` — błędny kod ukraiński i 2 braki

`src/Types/Lang.php:7-21` vs `api-components.json` → `schemas.locale.enum` (17 wartości) oraz
`ing-pay-paywall-topics/03-jezyk-bramki-platnosci-ing-pay.md` (15 wartości dla segmentu URL)
i `ing-pay-paywall-topics/08-widzet-dla-platnosci-kartami.md:56` (`data-locale`, 17 wartości).

| w spec, brak w repo | w repo, brak w spec |
|---|---|
| `uk`, `bg`, `sv` | `ua` (`src/Types/Lang.php:18`) |

`[BŁĄD]` `Lang::UA = 'ua'` (`src/Types/Lang.php:18`). Specyfikacja używa kodu **`uk`** (ISO 639-1
dla języka ukraińskiego), nie `ua` (to kod kraju ISO 3166). Skutki są dwa i oba realne:

1. `Environment::paywallUrl()` (`src/Types/Environment.php:24-25`) skleja segment ścieżki z
   `$lang->value`, więc powstaje `https://paywall.pay.ing.pl/ua/payment` — adres spoza listy
   dopuszczalnych z rozdziału 03.
2. `CustomerDto` castuje `locale` przez `Lang::class` (`src/DTO/Casts/CustomerDto.php:43`), więc
   `customer.locale` wysyłane do `POST /{merchantId}/transaction` i `POST /{merchantId}/payment`
   przyjmie wartość `ua`, odrzucaną przez `schemas.locale.enum` (422).

`[BŁĄD]` Ten sam cast jest użyty przy **odczycie** odpowiedzi (`responseCustomer.locale` w
`api-components.json`, m.in. `CancelPaymentResponseDto` → `src/DTO/Responses/CancelPaymentResponseDto.php:59`,
`GetPaymentResponseDto`). `BaseDto::castAttribute()` dla backed enuma wywołuje
`$castType::from($value)` (`src/DTO/BaseDto.php:58`) bez `tryFrom`/try-catch, więc odpowiedź API
zawierająca `locale: "uk"`, `"bg"` lub `"sv"` rzuci niezłapany `\ValueError` i wywróci cały parsing
odpowiedzi. Link płatności utworzony przez bramkę w języku ukraińskim, bułgarskim lub szwedzkim
wystarczy, żeby `Api::getPayment()` przestał działać.

`[BRAK]` `bg` (bułgarski) i `sv` (szwedzki) — obecne w `schemas.locale.enum` oraz w
`data-locale` widżetu, nieobecne w `Lang`.

`[ROZBIEŻNOŚĆ]` Niespójność typowania `locale` wewnątrz repo: `CustomerDto` wymusza `Lang`,
a `GetPaymentMethodsDto` trzyma `locale` jako nietypowany `string`
(`src/DTO/Api/GetPaymentMethodsDto.php:16,38` — brak wpisu w `$casts`), mimo że
`schemas.get-payment-methods.properties.locale` wskazuje na ten sam `schemas.locale`.

### 1.4 `TransactionSource` — zgodny

`src/Types/TransactionSource.php:9-10` vs `schemas.source.enum`.

| wartość | spec | repo |
|---|---|---|
| `web` | tak | `TransactionSource::WEB` (l. 10) |
| `api` | tak | `TransactionSource::API` (l. 9) |

`[INFO]` Pełna zgodność.

### 1.5 `TransactionStatus` — 2 nadmiarowe statusy

`src/Types/TransactionStatus.php:9-17` vs `schemas.status.enum` oraz
`ing-pay-api-topics/04-statusy-transakcji-i-linkow-platnosci.md` (obie listy identyczne, 7 wartości).

| status | spec (`schemas.status.enum` / rozdz. 04) | repo |
|---|---|---|
| `new` | tak | l. 9 |
| `pending` | tak | l. 11 |
| `authorized` | tak | l. 10 |
| `settled` | tak | l. 14 |
| `rejected` | tak | l. 13 |
| `cancelled` | tak | l. 16 |
| `error` | tak | l. 15 |
| `submitted_for_settlement` | **nie** | l. 12 |
| `refund` | **nie** | l. 17 |

`[NADMIAR]` `TransactionStatus::SUBMITTED_FOR_SETTLEMENT` (`src/Types/TransactionStatus.php:12`).
Ciąg `submitted_for_settlement` nie występuje nigdzie w `ing-pay-api.json` ani
`ing-pay-paywall.json` (zweryfikowane grepem) — to pozostałość po starszym imoje.

`[NADMIAR]` `TransactionStatus::REFUND` (`src/Types/TransactionStatus.php:17`). W ING Pay `refund`
jest **typem** transakcji, nie statusem — por. `schemas.type.enum` oraz
`api-components.json` → `responses.refund-response-200`, gdzie zwrot ma
`"type": "refund", "status": "settled"`. Mylące modelowanie: biblioteka sugeruje istnienie statusu,
którego API nigdy nie zwróci.

`[ROZBIEŻNOŚĆ]` Oba nadmiarowe statusy są wstrzykiwane do schematu walidacji notyfikacji
(`src/Lib/Validator.php:48` i `:75` przez `array_column(TransactionStatus::cases(), 'value')`),
więc walidacja przepuszcza statusy, których ING Pay nie wysyła. Kierunek bezpieczny (luźniejszy
niż spec), ale rozmywa kontrakt.

### 1.6 `TransactionType` — zgodny

`src/Types/TransactionType.php:9-10` vs `schemas.type.enum` (`sale`, `refund`). Pełna zgodność w obie
strony. `[INFO]`

### 1.7 `HashMethod` — zgodny

`src/Types/HashMethod.php:9-12` vs `ing-pay-paywall-topics/02-autoryzacja.md:7` oraz
`ing-pay-api-topics/06-notyfikacje.md:171`.

| wartość | spec | repo |
|---|---|---|
| `sha224` | tak | l. 9 |
| `sha256` | tak (domyślna w przykładach) | l. 10 |
| `sha384` | tak | l. 11 |
| `sha512` | tak | l. 12 |

`[INFO]` Pełna zgodność. Domyślna `HashMethod::SHA256` w `Utils::createSignature()`
(`src/Lib/Utils.php:20`) i `Utils::mockHeaders()` (`src/Lib/Utils.php:72`) jest zgodna z przykładem
z rozdziału 02 paywalla.

### 1.8 `WidgetType` — zgodny

`src/Types/WidgetType.php:9-11` vs `ing-pay-paywall-topics/08-widzet-dla-platnosci-kartami.md:41`
(`data-widget-type`: `oneclick`, `recurring`, `ecom3ds`). Pełna zgodność w obie strony. `[INFO]`

### 1.9 `PaymentMethod` / `PaymentMethodCode` — nota (audytuje inny agent)

`[ROZBIEŻNOŚĆ]` W skrócie: `PaymentMethod` ma 2 wartości spoza `schemas.paymentMethod.enum`
(`lease` — `src/Types/PaymentMethod.php:13`, poprawna tylko dla `schemas.visibleMethod`;
`visa_mobile` — `src/Types/PaymentMethod.php:17`, w specyfikacji jest to *kanał*, nie metoda),
a `PaymentMethodCode` ma 7 kodów spoza `schemas.paymentMethodCode.enum`
(`blik_oneclick`, `c2p`, `citi`, `envelo`, `ideabank`, `imoje_installments`, `tmobile`);
braków w drugą stronę nie ma w żadnym z obu enumów.

### 1.10 Enumy specyfikacji bez odpowiednika w `src/Types/`

`[BRAK]` `schemas.profileType.enum` (`FIRST`, `STANDARD`) — brak enuma. Używane przy OneClick/
recurring bez profilu kartowego; w repo `ChargeProfileDto`/`TransactionDto` nie mają tego pola
w ogóle (`src/DTO/Api/TransactionDto.php:52-65`).

`[BRAK]` `schemas.get-payment-methods.properties.device.enum` (`desktop`, `mobile`) — brak enuma;
`src/DTO/Api/GetPaymentMethodsDto.php:15,37` trzyma `device` jako nietypowany `string`.

`[BRAK]` `schemas.invoice.properties.buyer.properties.type.enum` (`PERSON`, `COMPANY`) oraz
`schemas.cart.properties.items.items.properties.taxStake.enum`
(`23, 22, 8, 7, 5, 3, 0, TAX_EXEMPT, TAX_NOT_LIABLE, TAX_REVERSE_CHARGE, TAX_EXCLUDING`)
i `.state.enum` (`NEW`, `USED`) — brak enumów; `invoice` i `cart` są w repo surowymi tablicami
(p. 7 poniżej).

---

## 2. Kody odpowiedzi HTTP

### 2.1 Pełna lista kodów, które API może zwrócić

Zestawienie `ing-pay-api-topics/05-kody-odpowiedzi-http.md` z deklaracjami `responses` w
`api-endpoints/*.json` (zweryfikowane programowo, wszystkie 19 plików):

| kod | rozdz. 05 | zadeklarowany w `api-endpoints/*.json` | obsługa w `Api::validateResponse` |
|---|---|---|---|
| `200` | tak | wszystkie endpointy | `$response->ok()` → `return` (`src/Lib/Api.php:240-242`) |
| `400` | tak | `post-merchantid-payment-cancel.json`, `post-merchantid-transaction-profile.json` | **brak** → `default => null` |
| `401` | tak | nie (tylko proza) | **brak** → `default => null` |
| `403` | tak | nie (tylko proza) | **brak** → `default => null` |
| `404` | tak | nie (tylko proza) | **brak** → `default => null` |
| `406` | tak | `post-merchantid-transaction.json` | **brak** → `default => null` |
| `422` | tak | `post-merchantid-transaction.json`, `post-merchantid-payment.json`, `post-merchantid-transaction-transactionid-refund.json` | `throw ApiErrorException` (`src/Lib/Api.php:245`) |
| `500` | tak | `post-merchantid-transaction.json` | `throw ApiErrorException` (`src/Lib/Api.php:246`) |
| `503` | tak | nie (tylko proza) | **brak** → `default => null` |
| `504` | tak | nie (tylko proza) | **brak** → `default => null` |

`[INFO]` Kody `409` i `429` nie występują w specyfikacji ING Pay w żadnej formie (zweryfikowane
grepem po `ing-pay-api.json` i wszystkich rozdziałach) — nie ma więc czego implementować, ale
`default => null` i tak przepuściłby je cicho.

### 2.2 Co dzieje się w repo przy nieobsłużonym kodzie

`[BŁĄD]` `src/Lib/Api.php:244-248` — `match` obsługuje wyłącznie `422` i `500`, a `default => null`
powoduje **ciche przejście dalej**. Dla 400/401/403/404/406/503/504 `validateResponse()` zwraca
`void` bez wyjątku, a wywołujący buduje DTO odpowiedzi z ciała błędu i zwraca je jako sukces.
Przykłady z realnym ryzykiem:

- `Api::cancelPayment()` (`src/Lib/Api.php:81-89`) — spec deklaruje 400 „Link nie może być anulowany”
  (`api-components.json` → `responses.cancel-payment-response-400`, ciało
  `{"apiErrorResponse": {"message": "Cannot cancel payment"}}`). Repo zwraca
  `CancelPaymentResponseDto`. `BaseDto::castAttributes()` (`src/DTO/BaseDto.php:26-28`) wymusza
  **każdy** klucz z `$casts` nawet gdy go nie ma w ciele, a `allowNull` dla tego DTO jest `false`,
  więc powstaje obiekt z `id === ''`, `amount === 0`, `isActive === false`, `status === null`
  (`src/DTO/Responses/CancelPaymentResponseDto.php:45-60`). Nieudane anulowanie linku jest nie do
  odróżnienia od udanego bez ręcznego sięgnięcia po `$dto->response->status()`.
- `Api::chargeProfile()` (`src/Lib/Api.php:112-120`) — spec deklaruje 400 „Profil jest nieaktywny”
  (`responses.debit-profile-response-400`, `code: TRX-ERROR-120301`). Repo zwraca
  `ChargeProfileResponseDto` z `transaction` zrzutowanym na pusty
  `src/DTO/Casts/TransactionDto.php` (`allowNull = true`, l. 50) — wszystkie pola `null`.
  **Obciążenie profilu, które się nie powiodło, wygląda jak transakcja o statusie `null`.**
- `Api::createTransaction()` (`src/Lib/Api.php:41-49`) — 406 „metoda/kanał nieaktywny”
  (`responses.transaction-response-406`) przechodzi cicho i zwraca pusty `TransactionResponseDto`.
  Rozdział 05 poświęca temu kodowi osobną sekcję z instrukcją obsługi (wyłączenie kanału,
  `visibleMethod`, `isOnline`) — repo nie daje żadnego sygnału, że kod wystąpił.

`[BŁĄD]` `Api::validateResponse()` nie reaguje na 401/403. Rozdział 01 i `securitySchemes`
(`api-components.json` → `securitySchemes."Token autoryzacyjny"`) wskazują Bearer jako jedyny
mechanizm — zły lub wygasły token kończy się pustym DTO zamiast wyjątku, co jest typową przyczyną
„cicho nic się nie dzieje” na produkcji.

`[ROZBIEŻNOŚĆ]` Brak obsługi 503/504, dla których rozdział 05 zaleca konkretną strategię
(tymczasowe wyłączenie płatności i retry po ~5 min pojedynczym `GET`). Przy tych kodach ciało
odpowiedzi może w ogóle nie być JSON-em, więc `ResponseDto::__construct()`
(`src/DTO/Responses/ResponseDto.php:17`, `$incomingData->collect()`) zbuduje pusty DTO.

`[ROZBIEŻNOŚĆ]` `Response::ok()` (`src/Lib/Api.php:240`) jest ścisłym `status() === 200`.
Specyfikacja nie deklaruje 201/204 dla żadnego endpointu, więc dziś to nieszkodliwe — ale każdy
nie-200 sukces też wpadłby w `default => null`.

`[ROZBIEŻNOŚĆ]` Brak jakiegokolwiek testu kodów błędów: `grep` po `tests/` nie znajduje literałów
`400|401|403|404|406|409|422|429|500|503|504`. `tests/Lib/ApiSpecTest.php` testuje wyłącznie
ścieżki 200.

`[ROZBIEŻNOŚĆ]` `docs/api.md` nie zawiera ani słowa o `ApiErrorException`, wyjątkach ani kodach
HTTP (`grep -i "error|exception|status"` → 0 trafień), mimo że `docs/notifications.md:13,16`
dokumentuje wyjątki dla ścieżki notyfikacji. Konsument nie dowie się z dokumentacji, że 422/500
rzucają, a 406 nie.

---

## 3. Struktura błędu

### 3.1 `ApiErrorResponseDto` vs `schemas.apiErrorResponse`

| pole w spec | typ w spec | w repo | ocena |
|---|---|---|---|
| `code` | `string` | `src/DTO/Responses/ApiErrorResponseDto.php:10,18` — cast `'string'` | zgodne |
| `message` | `string` | `:11,19` — cast `'string'` | zgodne |
| `instance` | `object` (kopia body żądania) | `:12` tylko `@property-read mixed`, brak w `$casts` | `[ROZBIEŻNOŚĆ]` |
| `errors` | `array` obiektów `{property, message}` | `:13,20` — cast `'array'` | `[BŁĄD]`, patrz niżej |
| `additionalProperties` | dowolne | przechodzą (`Fluent`) | zgodne |

`[BŁĄD]` Cast `'errors' => 'array'` (`src/DTO/Responses/ApiErrorResponseDto.php:20`) **nie działa**.
`BaseDto::castAttribute()` (`src/DTO/BaseDto.php:61-67`) nie ma gałęzi `'array'`, więc wartość wpada
w `default => $value`. Gdy API nie przyśle klucza `errors` (a nie przysyła go np. przy
`cancel-payment-response-400`, gdzie ciało to samo `{"message": ...}`), `castAttributes()`
(`src/DTO/BaseDto.php:27`) ustawia `errors = null`. Docblock deklaruje `@property-read array $errors`
(`:13`), więc konsument piszący `foreach ($e->dto->errors as $error)` dostanie
`TypeError: foreach() argument must be of type array|object, null given`. To samo dotyczy `code`
i `message`: `(string) null` daje `''`, więc brakujący `code` jest nie do odróżnienia od pustego.

`[BRAK]` Brak DTO dla elementu `errors[]`. Spec definiuje `schemas.errors.items` jako obiekt z
`property` („Miejsce wystąpienia błędu”) i `message` („Powód wystąpienia błędu”) — przykład z
`responses.transaction-response-422`: `{"property": "instance", "message": "requires property \"customer\""}`.
Repo nie ma odpowiednika (żadnego `ErrorDto`/`ValidationErrorDto` w `src/DTO/`), więc konsument
pracuje na surowych tablicach asocjacyjnych.

`[INFO]` Nazewnictwo w repo jest poprawne: pole nazywa się `property`, nie `field` — i docblock
tego nie myli. Pól `status` ani `error` schemat `apiErrorResponse` nie posiada, więc ich brak w DTO
jest prawidłowy.

### 3.2 `ApiErrorException`

`[INFO]` `src/Exceptions/ApiErrorException.php:25-27` poprawnie rozpakowuje kopertę
`{"apiErrorResponse": {...}}` — wszystkie odpowiedzi błędne w `api-components.json`
(400/406/422/500) są tak owinięte, więc logika jest zgodna ze specyfikacją.

`[BŁĄD]` `src/Exceptions/ApiErrorException.php:33` — `parent::__construct($this->dto->message, 422)`
zapisuje **na sztywno 422** jako kod wyjątku, również dla odpowiedzi 500 rzucanej z
`src/Lib/Api.php:246`. `$exception->getCode()` zawsze zwróci 422, co psuje każde rozgałęzienie typu
„retry przy 500, nie retry przy 422” zalecane w rozdziale 05. Prawdziwy kod jest dostępny tylko
pośrednio przez `$exception->response->status()`.

`[ROZBIEŻNOŚĆ]` `src/Lib/Api.php:246` rzuca `new ApiErrorException($response)` **bez** drugiego
argumentu, więc dla błędów 500 `$exception->request` jest puste i `context()`
(`src/Exceptions/ApiErrorException.php:36-42`) nie zawiera payloadu żądania — akurat tam, gdzie
rozdział 05 radzi zapamiętać metodę/kanał, żeby go tymczasowo wyłączyć. Dla 422
(`src/Lib/Api.php:245`) request jest przekazywany.

`[ROZBIEŻNOŚĆ]` `$this->dto->message` może być `''` (patrz 3.1), więc wyjątek bywa bez komunikatu.

---

## 4. Środowiska i URL-e

`src/Types/Environment.php` vs `servers` w obu specyfikacjach i URL-e z rozdziałów.

| element | specyfikacja | repo | ocena |
|---|---|---|---|
| API produkcja | `https://api.pay.ing.pl/v1/merchant` (`ing-pay-api.json` → `servers[0]`) | `apiUrl()` → `https://api.pay.ing.pl/v1` (`:15`) + `'merchant'` doklejane w `src/Lib/Url.php:17` | zgodne |
| API sandbox | `https://api.sandbox.pay.ing.pl/v1/merchant` (`servers[1]`) | `https://api.sandbox.pay.ing.pl/v1` (`:16`) + `'merchant'` | zgodne |
| Paywall produkcja | `https://paywall.pay.ing.pl` (`ing-pay-paywall.json` → `servers[0]`), ścieżka `/payment` i `/[lang]/payment` (paywall rozdz. 03) | `:24,30` | zgodne |
| Paywall sandbox | `https://paywall.sandbox.pay.ing.pl` (`servers[1]`) | `:25,31` | zgodne |
| Widget JS produkcja | `https://paywall.pay.ing.pl/js/widget.min.js` (paywall rozdz. 08, l. 12) | `:38` | zgodne |
| Widget JS sandbox | `https://paywall.sandbox.pay.ing.pl/js/widget.min.js` (paywall rozdz. 08, l. 18) | `:39` | zgodne |
| CDN logotypów | `https://cdn.pay.ing.pl/img/pay/<kod>.png` | `https://data.imoje.pl` (`:45`) | **`[BŁĄD]`** |

`[BŁĄD]` `Environment::cdnUrl()` (`src/Types/Environment.php:43-46`) zwraca `https://data.imoje.pl`,
podczas gdy cała specyfikacja ING Pay używa `https://cdn.pay.ing.pl`. Potwierdzenia w spec
(zweryfikowane programowo, wszystkie wystąpienia hosta CDN):
`api-components.json` → `responses.service-response-200...paymentMethods[0].paymentMethodImage.png`
= `https://cdn.pay.ing.pl/img/pay/blik.png`, analogicznie `paymentMethodCodeImage.png`,
`responses.services-response-200...[1]...paymentMethodCodeImage.png` = `.../gpay.png`,
`responses.get-payment-methods-response-200...image[0].png` i `...channels[0].image[0].png`,
oraz `ing-pay-api-topics/16-kolekcja-postman.md:7` (`https://cdn.pay.ing.pl/example/imoje-postman.zip`).
Konsekwencja: `PaymentMethodCode::getLogo()` (`src/Types/PaymentMethodCode.php:75`) buduje
`https://data.imoje.pl/img/pay/<plik>` — adres z wycofanej domeny, niepotwierdzony w specyfikacji
ING Pay. Commit `b6821f4` zmigrował hosty API i paywalla na `pay.ing.pl`, ale CDN pominął.
Te same stare adresy są utrwalone w fabrykach: `src/Factories/Casts/ServiceDataDtoFactory.php:32-33`
i `src/Factories/Responses/PaymentMethodsResponseDtoFactory.php:21,31`.

`[ROZBIEŻNOŚĆ]` Rozszerzenia plików logotypów. Specyfikacja pokazuje tylko warianty `.png`
(`blik.png`, `gpay.png`, `wallet.png`), a `PaymentMethodCode::getLogoFilename()`
(`src/Types/PaymentMethodCode.php:80-128`) twardo przypisuje `.svg` dla 24 kodów (m.in.
`self::GOOGLE_PAY => 'gpay.svg'`, `:96`) z komentarzami `// missing SVG` przy pozostałych.
Dostępność wariantów SVG nie jest w specyfikacji udokumentowana — to wiedza pochodząca spoza spec,
niemożliwa do zweryfikowania z dostarczonych źródeł.

`[ROZBIEŻNOŚĆ]` `cdnUrl()` ignoruje `$this` (`src/Types/Environment.php:43-46`) i zawsze zwraca
jeden host; `getLogo()` dodatkowo wymusza `Environment::PRODUCTION` (`src/Types/PaymentMethodCode.php:75`),
więc w sandboxie logotypy wskazują na produkcję. Specyfikacja nie podaje sandboxowego CDN, więc
zachowanie jest obronne, ale nieudokumentowane.

`[ROZBIEŻNOŚĆ]` Specyfikacja nie wystawia drugiego hosta API poza `servers`, natomiast repo
rozdziela `apiUrl()` (`/v1`) i segment `merchant` w `Url` — efekt końcowy zgodny, ale
`Environment::apiUrl()` zwraca adres, który sam w sobie nie jest żadnym `servers[].url`,
co utrudnia porównanie 1:1.

`[INFO]` Panele administracyjne z rozdziałów 00 i 15 (`https://pay.ing.pl`,
`https://sandbox.pay.ing.pl`) nie wymagają reprezentacji w kodzie. Wartości enuma
`Environment` (`production`, `sandbox`) odpowiadają opisom `servers[].description`
(„Środowisko produkcyjne” / „Środowisko testowe”).

`[INFO]` Dane testowe sandboxa z rozdz. 15 API i rozdz. 15 paywalla (karty Visa, kody BLIK
`888000/888111/888222`, tytuły `TEST-100000`…`TEST-100003`, kwoty wyzwalające `CODE_*`
z rozdz. 13: 15 gr → `CODE_05`, 151 gr → `CODE_51`, 1141 gr → `CODE_114`, 14143 gr → `CODE_AC`)
nie mają żadnego odwzorowania w repo. To materiał dokumentacyjny, nie kontrakt — brak
odwzorowania nie jest wadą, ale warto o nim wiedzieć przy pisaniu testów integracyjnych.

---

## 5. Autoryzacja i nagłówki

`[INFO]` `Api::request()` (`src/Lib/Api.php:226-231`) używa `Http::withToken($this->config->apiKey)`,
co daje `Authorization: Bearer <token>` — dokładnie jak
`ing-pay-api-topics/01-authentication.md` i `api-components.json` →
`securitySchemes."Token autoryzacyjny"` (`type: http`, `scheme: bearer`).
`ing-pay-api.json` → `security` stosuje ten schemat globalnie, a repo stosuje go do wszystkich
19 metod `Api` — zgodne.

`[INFO]` `Http::asJson()->acceptJson()` (`src/Lib/Api.php:228-229`) ustawia
`Content-Type: application/json` i `Accept: application/json`. Wszystkie 9 `requestBody`
w `ing-pay-api.json` deklaruje wyłącznie `application/json` (zweryfikowane programowo) — zgodne
z rozdziałem 02.

`[INFO]` Źródło tokenu: `services.imoje.api_key` (`src/ImojeServiceProvider.php:28`) jako osobny
klucz od `service_key` używanego do sygnatur (`src/Lib/Utils.php:22,32,75`) — rozdzielenie zgodne
z rozdziałem 00 („Token autoryzacyjny” vs „Klucz sklepu”).

`[ROZBIEŻNOŚĆ]` Brak jakiejkolwiek walidacji obecności/niepustości `api_key` — `Config`
(`src/Lib/Config.php:11-17`) przyjmuje `string` bez sprawdzenia, a `Environment::from()`
(`src/ImojeServiceProvider.php:29`) rzuci `\ValueError` przy literówce w `services.imoje.env`.
Przy pustym tokenie API odpowie 401, który (p. 2.2) przechodzi cicho.

`[ROZBIEŻNOŚĆ]` Brak konfiguracji timeoutu i retry na `PendingRequest`
(`src/Lib/Api.php:226-231`), mimo że rozdział 05 wprost opisuje scenariusze 500/503/504
i zaleca ponawianie po przerwie.

---

## 6. Limity kwot transakcji

`ing-pay-api-topics/14-minimalne-i-maksymalne-wartosci-kwot-transakcji.md`: *„Dla każdej metody
płatności obowiązują limity dokonania transakcji z dokładnością do kanału płatności. Bieżące
wartości używanych limitów można uzyskać w metodzie `service` lub `get-payment-methods`.”*
Struktura: obiekt `transactionLimits` z `maxTransaction.{type,value}` i `minTransaction.{type,value}`.

Lokalizacja w schemacie (zweryfikowana programowo, 5 wystąpień):
`api-components.json` →
`responses.service-response-200...schema.properties.service.properties.paymentMethods.items.properties.transactionLimits`
oraz analogicznie w `responses.services-response-200`. Przykłady ze spec:
`{"maxTransaction": {"type":"number","value":5000000}, "minTransaction": {"type":"number","value":1}}`
i `{"maxTransaction": {...,"value":99999999}, "minTransaction": {...,"value":5}}`.

`[ROZBIEŻNOŚĆ]` **Repo nie waliduje nigdzie kwot transakcji.** Zweryfikowane:
- `src/Lib/Validator.php` nie ma żadnej metody walidującej żądanie — jedyny schemat
  (`fromNotification`, `:26-89`) dotyczy **przychodzących notyfikacji** i wymusza
  `amount: {type: integer, minimum: 0, exclusiveMinimum: true}` (`:37-41`), czyli tylko „> 0”.
- `src/DTO/Api/TransactionDto.php:54` i `src/DTO/Api/PaymentDto.php:41` castują `amount` na `'int'`
  bez żadnego zakresu; `src/DTO/Api/RefundDto.php`, `ChargeProfileDto` — tak samo.
- `grep -rin "transactionLimit|maxTransaction|minTransaction" src/` daje **wyłącznie** trafienia
  w fabryce testowej `src/Factories/Casts/ServiceDataDtoFactory.php:28-30`.

Skutek: przekroczenie limitu kanału objawi się dopiero jako 422 z API — a 422 akurat jest
obsłużone wyjątkiem, więc ryzyko jest umiarkowane (brak cichej awarii, tylko brak walidacji
po stronie klienta). Dlatego `[ROZBIEŻNOŚĆ]`, nie `[BŁĄD]`.

`[BRAK]` Brak typowanego dostępu do limitów zwróconych przez API. `ServiceDataDto` trzyma
`paymentMethods` jako nietypowaną tablicę (`src/DTO/Casts/ServiceDataDto.php:15`, brak wpisu
w `$casts` l. 23-26), a `PaymentMethodsResponseDto` zawija całą odpowiedź w jeden klucz
`methods` jako surową tablicę (`src/DTO/Responses/PaymentMethodsResponseDto.php:11,22-25`).
Konsument chcący zastosować się do rozdziału 14 musi ręcznie grzebać w tablicach
(`$dto->methods[0]['transactionLimits']['maxTransaction']['value']`) — brak DTO
`TransactionLimitsDto` / `PaymentMethodDto`. To samo dotyczy flagi `isOnline`, którą rozdział 05
wskazuje jako sposób uniknięcia błędu 406.

`[INFO]` Specyfikacja **nie podaje** żadnych stałych, globalnych limitów kwot — są one per sklep
i per kanał, pobierane w runtime. Zahardkodowanie wartości w bibliotece byłoby więc błędem;
właściwą implementacją jest wystawienie limitów z odpowiedzi `service`/`get-payment-methods`
w postaci typowanej.

---

## 7. Funkcje ze specyfikacji bez wsparcia w repo

### 7.1 Google Pay / Apple Pay 0 level (rozdz. 09)

`[BRAK]` **Obiekt `wallet` nie istnieje w repo.** Zweryfikowane:
`grep -rin "wallet|gpay|applepay|apple_pay|googlepay" src/` zwraca wyłącznie wpisy enumów
(`src/Types/PaymentMethod.php:16` `WALLET = 'wallet'`, `src/Types/PaymentMethodCode.php:10,26`
`APPLEPAY`/`GOOGLE_PAY`) i mapowania logotypów (`:82,96`). Nie ma ani pola `wallet` w
`src/DTO/Api/TransactionDto.php` (ani w `$casts` l. 52-65, ani w kształcie tablicy l. 68-91),
ani klasy `WalletDto`/`PaymentDataDto`/`WalletHeaderDto` w `src/DTO/Casts/`.

Brakuje dokładnie (wg `api-components.json` → `schemas.wallet`, użytego w
`schemas.transaction.properties.wallet`):
- Google Pay 0 level: `wallet.signature`, `wallet.protocolVersion`, `wallet.signedMessage`
  (wszystkie `string`).
- Apple Pay 0 level: `wallet.paymentData` (`required: version, data, signature, header`) oraz
  `wallet.paymentData.header` (`required: ephemeralPublicKey, publicKeyHash, transactionId`).

`[BRAK]` **`additionalData` bez typowania.** Rozdział 09 wymaga obiektu `additionalData.browser`
przy obu metodach 0 level. W repo `additionalData` istnieje tylko jako surowa tablica i jest
jawnie odłożone na później: `src/DTO/Api/TransactionDto.php:62` zawiera zakomentowany
`// 'additionalData' => 'array' @todo`, docblock `:39,87`. Fabryka
`src/Factories/Api/TransactionDtoFactory.php:42-43` buduje `additionalData.browser` ręcznie,
więc kształt jest znany, ale nie ma `AdditionalDataDto`/`BrowserDto` z polami `ip`, `language`,
`jsEnabled`, `timezoneOffset`, `userAgent`, `accept`, `javaEnabled`, `screenColorDepth`,
`screenHeight`, `screenWidth`.

`[INFO]` Po stronie odpowiedzi wsparcie jest: rozdział 09 mówi o przekierowaniu na `action.url`
przy 3DS, a `src/DTO/Casts/ActionDto.php` i `src/DTO/Responses/TransactionResponseDto.php:24`
to obsługują. Brakuje więc wyłącznie strony żądania.

`[INFO]` Nie trzeba nowych endpointów — Google/Apple Pay 0 level działa na istniejącym
`POST /{merchantId}/transaction`, który repo już wywołuje (`src/Lib/Api.php:41-49`).

### 7.2 ING Księgowość (rozdz. 11, pole `isKsef`)

`[BRAK]` **`isKsef` nie istnieje w repo.** `grep -rin "ksef" src/` → 0 trafień.
Specyfikacja umieszcza to pole wyłącznie w `schemas.payment.properties.isKsef`
(`boolean`, „Czy generować identyfikator płatności KSeF (IPKSeF)”), czyli w ciele
`POST /{merchantId}/payment`. `src/DTO/Api/PaymentDto.php` nie ma go ani w `$casts` (l. 40-44),
ani w kształcie tablicy (l. 47-65) — a `BaseDto` dziedziczy po `Fluent`, więc nieznany klucz
przeszedłby, tylko bez typowania, dokumentacji i castowania na `bool`.

`[BRAK]` **`invoice` brakuje w `PaymentDto`.** Rozdział 11 wymaga obiektu `invoice` zarówno przy
transakcji, jak i przy linku płatności („do podstawowego zapytania tworzącego transakcję **lub**
link płatności należy załączyć obiekt `invoice`”), a `schemas.payment.properties` faktycznie
zawiera `invoice`. W repo `invoice` figuruje tylko w `src/DTO/Api/TransactionDto.php:42,64,90`
i `src/DTO/Api/ChargeProfileDto.php:22,49-51` — i to jako nietypowana tablica z `@todo`
(`TransactionDto.php:64`: `// 'invoice' => 'array' @todo`; `ChargeProfileDto.php:49`:
`// @todo: add proper DTO for data and invoice`). `PaymentDto` nie ma go wcale.

`[BRAK]` **Brak DTO dla `invoice`.** `schemas.invoice` to rozbudowana struktura z
`required: [buyer, positions]`, `buyer.required: [type, email, fullName, street, city, postalCode,
countryCodeAlpha2]` i enumem `buyer.type` (`PERSON`/`COMPANY`), z wzorcami znaków i limitami
długości na każdym polu. Nic z tego nie jest w repo odwzorowane — ani klasa, ani enum, ani
walidacja. Specyfikacja dodaje też ograniczenie biznesowe („Obiekt nie może być użyty, jeśli
w sklepie włączona jest opłata serwisowa”), którego repo nie może wyegzekwować, bo nie czyta
konfiguracji sklepu.

### 7.3 ING Lease Now (rozdz. 12)

`[BRAK]` **Brak DTO dla `cart`.** Rozdział 12: do zapytania tworzącego **link płatności** należy
załączyć obiekt `cart` z tablicą `items`. W repo `cart` istnieje wyłącznie jako nietypowana
tablica: `src/DTO/Api/PaymentDto.php:30` (`@property-read array $cart`) i `:64`
(`cart?: array<mixed>`), bez wpisu w `$casts` (l. 40-44). Brakuje klas `CartDto` / `CartItemDto`
pokrywających `schemas.cart.properties.items.items` z `required: [id, name, amount, tax, taxStake,
quantity, url, categoryId]` oraz polami opcjonalnymi `unit`, `state`, `discount.amount`,
`discount.tax` — w tym dwóch enumów wymienionych w p. 1.10 (`taxStake`, `state`).

`[ROZBIEŻNOŚĆ]` `PaymentMethod::LEASE = 'lease'` (`src/Types/PaymentMethod.php:13`, komentarz
`// ING Lease Now`) jest jedynym śladem Lease Now w typach. Wartość `lease` występuje w
specyfikacji wyłącznie w `schemas.visibleMethod.enum` i `schemas.visibleMethodTransaction.enum`,
**nie** w `schemas.paymentMethod.enum` — więc użycie `PaymentMethod::LEASE` jako
`paymentMethod` w `POST /{merchantId}/transaction` skończy się odrzuceniem przez API.
Jednocześnie `visibleMethod` jest w repo zwykłą tablicą stringów
(`src/DTO/Api/TransactionDto.php:38,83`; `src/DTO/Api/PaymentDto.php:23,57`), więc enum nie jest
tam w ogóle wykorzystywany.

`[INFO]` `ing-pay-paywall-topics/14-ing-lease-now.md` i `13-ing-ksiegowosc.md` opisują te same
funkcje od strony bramki (parametry `data-invoice`, `data-multipayout` widżetu — paywall rozdz. 08,
l. 54-55). `src/DTO/Paywall/TransactionDto.php:16-32` nie ma ani `invoice`, ani `multipayout`,
ani `cart` — ale ta część należy do audytu paywalla.

### 7.4 Kody odpowiedzi dla transakcji kartowych (rozdz. 13)

`[INFO]` `statusCode` i `statusCodeDescription` **są** w repo obecne:
`src/DTO/Casts/TransactionDto.php:36-37` (`@property-read string|null`). Nie mają wpisu
w `$casts` (l. 52-70), więc przechodzą przez `Fluent` jako surowe wartości — to wystarcza,
bo spec opisuje je jako otwartą listę („W przypadku braku kodu z poniższej listy zwracana zostaje
wartość bezpośrednio od dostawcy”).

`[ROZBIEŻNOŚĆ]` Brak enuma / stałych dla ~60 kodów z tabeli rozdziału 13
(`INT_CREDIT_CARD_DISABLED`, `CODE_000`, `CODE_01`…`CODE_Z3`). Zamknięty enum byłby tu wręcz
szkodliwy (spec dopuszcza wartości spoza listy), ale brakuje jakiejkolwiek pomocy w interpretacji
— np. mapy kod → opis czy predykatu „czy to odmowa banku”. Decyzja projektowa, nie błąd,
ale wartościowa luka funkcjonalna dla konsumenta.

---

## 8. `NotImplementedException` i `CantChangeTransactionStatus`

`[NADMIAR]` **`src/Exceptions/NotImplementedException.php` to martwy kod.** Klasa nie jest rzucana
ani importowana nigdzie: `grep -rn "NotImplementedException"` po całym repo (bez `vendor/`)
z wykluczeniem samego pliku definicji daje **0 trafień** — ani w `src/`, ani w `tests/`,
ani w `docs/`. Komunikat „Given response has been not recognized” i kod 501 sugerują, że miała
obsługiwać nierozpoznane kształty odpowiedzi API — czyli dokładnie lukę opisaną w p. 2.2, gdzie
dziś stoi `default => null` (`src/Lib/Api.php:247`). Specyfikacja nie zna pojęcia „nierozpoznanej
odpowiedzi” (wszystkie `responses` są zdefiniowane), więc klasa nie ma pokrycia w spec — ale ma
oczywiste zastosowanie jako element naprawy `validateResponse()`.

`[NADMIAR]` **`src/Exceptions/CantChangeTransactionStatus.php` to martwy kod.** Również 0 trafień
poza plikiem definicji. Jedyny kod, który logicznie mógłby ją rzucić — `TransactionStatus::canChange()`
(`src/Types/TransactionStatus.php:19-46`) — zwraca `bool` i również **nigdy nie jest wywoływany**
(`grep -rn "canChange"` po całym repo → wyłącznie definicja metody w l. 19).

`[NADMIAR]` **`TransactionStatus::canChange()` nie ma pokrycia w specyfikacji.**
`ing-pay-api-topics/04-statusy-transakcji-i-linkow-platnosci.md` wymienia statusy, ale **nie
definiuje żadnej maszyny stanów** ani dozwolonych przejść; `api-components.json` →
`schemas.status` to płaski enum bez `x-transitions` czy podobnych. Reguły zaszyte w metodzie są
więc wiedzą spoza spec, a jedna z nich jest wprost sprzeczna z modelem ING Pay:
`src/Types/TransactionStatus.php:25-27` traktuje `settled → refund` jako dozwolone przejście
statusu, podczas gdy w ING Pay zwrot to **osobna transakcja typu `refund`** o własnym statusie
(`responses.refund-response-200`: `{"type": "refund", "status": "settled"}`), a nie zmiana statusu
transakcji pierwotnej.

`[ROZBIEŻNOŚĆ]` `src/Exceptions/CantChangeTransactionStatus.php:11` ustawia tylko
`protected $message` bez konstruktora i bez kontekstu (w przeciwieństwie do pozostałych wyjątków
pakietu, które mają `context()`: `ApiErrorException:36`, `NotImplementedException:19`,
`SchemaValidationException`). Niespójność konwencji wewnątrz `src/Exceptions/`.

`[INFO]` Dla porównania, trzeci martwy wyjątek: `src/Exceptions/InvalidDtoNameException.php`
również nie jest nigdzie rzucany (0 trafień poza definicją). Pozostałe cztery wyjątki mają
pokrycie: `ApiErrorException` (`src/Lib/Api.php:245-246`), `SchemaValidationException`
(`src/Lib/Validator.php:116`), `InvalidSignatureException` (`src/Lib/Validator.php:133,143`),
`ReadOnlyDtoException` (`src/DTO/BaseDto.php:80`).

`[INFO]` `CONTRIBUTING.md:31` klasyfikuje pojawienie się/zniknięcie udokumentowanego wyjątku jako
zmianę **major**. Ponieważ żaden z trzech martwych wyjątków nie jest rzucany ani udokumentowany
w `docs/`, ich usunięcie (lub podłączenie `NotImplementedException` do `validateResponse()`)
nie powinno być zmianą łamiącą w rozumieniu tego dokumentu — poza samym usunięciem publicznej
klasy, które już nią jest.
