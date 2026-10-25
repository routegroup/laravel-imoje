# Notyfikacje

Zakres: `ing-pay-api-topics/06-notyfikacje.md` + `ing-pay-paywall-topics/05-notyfikacje.md` (oba przejrzane w całości),
pomocniczo `api-components.json` (`schemas.action`, `schemas.paymentProfile`, `schemas.status`, `schemas.paymentMethod`,
`schemas.paymentMethodCode`, `schemas.currency`, `responses.get-transaction-response-200`),
`ing-pay-api-topics/13-obsluga-kodow-dla-transakcji-kartowych.md`, `ing-pay-api-topics/07-preautoryzacja-transakcji.md`,
`ing-pay-paywall-topics/02-autoryzacja.md`.

Repo: `/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a` (worktree nietknięty,
`vendor/` nie było i nie zostało zainstalowane — analiza statyczna kodu, bez uruchamiania testów).

## Podsumowanie ustaleń

| Etykieta | Liczba | Identyfikatory |
|---|---|---|
| `[BŁĄD]` | 10 | N-01, N-02, N-03, N-08, N-11, N-14, N-15, N-37, N-41, N-43 |
| `[BRAK]` | 12 | N-09, N-13, N-17, N-18, N-19, N-20, N-31, N-33, N-34, N-35, N-39, N-42 |
| `[ROZBIEŻNOŚĆ]` | 16 | N-04, N-07, N-12, N-15b, N-16, N-21, N-22, N-24, N-25, N-26, N-31b, N-38, N-40, N-44, N-45, N-46 |
| `[NADMIAR]` | 4 | N-23, N-27, N-28, N-30 |
| `[INFO]` | 9 | N-00, N-05, N-06, N-29, N-29b, N-32, N-36, N-47, N-48 |
| **Razem** | **51** | |

Pięć najpoważniejszych: **N-01** (brak `hash_equals` — timing attack na jedynym mechanizmie uwierzytelniania),
**N-02** (`\ValueError` zamiast `InvalidSignatureException` przy braku/nieznanym `alg` → 500 i 23 ponowienia),
**N-03** (`merchantid`/`serviceid` nigdy nieweryfikowane), **N-14** (`array_filter` kasuje puste stringi i zera
z payloadu, m.in. `title` i `statusCodeDescription`), **N-11** (schemat walidacji przepuszcza dowolny obiekt).

## Porównanie obu rozdziałów spec

`[INFO] N-00` — **Rozdziały są merytorycznie identyczne.** `diff` obu plików daje wyłącznie:
różnice formatowania tabel (padding spacji), interpunkcję w liście cyklu ponowień (przecinki),
inny anchor linku wewnętrznego, dopisek „(np. sklep)" w wersji paywall oraz inne **wartości przykładowe**
(API: `paymentMethod: "blik"` / `paymentMethodCode: "blik"`, `title: ""`, `orderId: "Zamowienie test"`;
paywall: `"pbl"` / `"ipko"`, `title: "yourTitle"`, `orderId: "yourOrderId"`).
**Żadna wersja nie jest szersza** — zbiór pól, nagłówków, algorytmów, cyklu ponowień i wymaganej odpowiedzi
jest w obu identyczny. Dalej w raporcie cytuję rozdział API; wszystko obowiązuje tak samo dla paywalla.

Uwaga: wersja API w przykładzie weryfikacji podpisu używa `"title": ""` — pusty string jest więc
w spec **jawnie dopuszczoną wartością produkcyjną** (ma znaczenie dla `N-14`).

## Struktura payloadu

Legenda kolumny „schemat walidatora": `src/Lib/Validator.php:30-86` (`fromNotification`).
Kolumna „DTO repo": `GenericNotificationDto` → `src/DTO/Casts/*`.
„raw" = klucz przechodzi do `Fluent::$attributes` bez rzutowania (nie ma go w `$casts`), czyli dane nie giną,
ale nie ma typu, PHPDoc-a ani gwarancji.

### Obiekt `transaction` → `src/DTO/Casts/TransactionDto.php`

| Pole ze spec | Typ wg spec | Wymagane wg spec | Schemat walidatora | DTO repo | Status |
|---|---|---|---|---|---|
| `id` | string(36) UUIDv4 | nieoznaczone | **nie ma** | `'string'` (`TransactionDto.php:53`) | `[ROZBIEŻNOŚĆ] N-12` |
| `type` | enum `sale`,`refund` | nieoznaczone | jest, enum z `TransactionType` (`Validator.php:56-59`), w `required` | `TransactionType` (`:54`) | OK |
| `status` | enum `new`,`pending`,`authorized`,`settled`,`cancelled`,`rejected` | nieoznaczone | jest, enum = **wszystkie 9** case'ów `TransactionStatus` (`Validator.php:46-49`), w `required` | `TransactionStatus` (`:55`) | `[NADMIAR] N-23` |
| `source` | enum `api`,`web` | nieoznaczone | **nie ma** | `TransactionSource` (`:56`) | OK (enum pokrywa się 1:1) |
| `created` | integer(10) | nieoznaczone | **nie ma** | `'int'` (`:57`) | OK |
| `modified` | integer(10) | nieoznaczone | **nie ma** | `'int'` (`:58`) | OK |
| `notificationUrl` | varchar(300) | nieoznaczone | **nie ma** | **brak w `$casts`**, tylko PHPDoc `:24` → raw | `[ROZBIEŻNOŚĆ] N-21` |
| `serviceId` | string(36) UUIDv4 | nieoznaczone | jest, `type: string`, w `required` (`Validator.php:53-55`) | `'string'` (`:59`) | `[BŁĄD] N-03` (nieporównywane z konfiguracją), `[ROZBIEŻNOŚĆ] N-25` |
| `amount` | integer(9), max 999999999 | nieoznaczone | jest, `integer` + `exclusiveMinimum: 0`, **brak `maximum`** (`Validator.php:37-41`) | `'int'` (`:60`) | `[ROZBIEŻNOŚĆ] N-25` |
| `currency` | string(3) ISO 4217 | nieoznaczone | jest, enum z `Currency` (`Validator.php:42-45`) | `Currency` (`:61`) | OK (nadzbiór, `N-27`) |
| `title` | string(255) | nieoznaczone | **nie ma** | **brak w `$casts`**, PHPDoc `:28` → raw, **i kasowane gdy `""`** | `[BŁĄD] N-14` |
| `orderId` | string(100), ograniczony alfabet | nieoznaczone | jest, `type: string`, bez `maxLength`/`pattern`, w `required` (`:50-52`) | `'string'` (`:62`) | `[ROZBIEŻNOŚĆ] N-25` |
| `paymentMethod` | **string** (bez enum w tabeli) | nieoznaczone | **nie ma** | `PaymentMethod` — zamknięty enum, `::from()` (`:63`) | `[ROZBIEŻNOŚĆ] N-26` |
| `paymentMethodCode` | **string** (bez enum w tabeli) | nieoznaczone | **nie ma** | `PaymentMethodCode` — zamknięty enum, `::from()` (`:64`) | `[ROZBIEŻNOŚĆ] N-26` |
| `statusCode` | string(24) | tylko karty | **nie ma** | **brak w `$casts`**, PHPDoc `:36` → raw | `[ROZBIEŻNOŚĆ] N-21` |
| `statusCodeDescription` | string(113) | tylko karty, **spec: „zwracana jest pusta wartość"** | **nie ma** | **brak w `$casts`**, PHPDoc `:37` → raw, **i kasowane gdy `""`** | `[BŁĄD] N-14` |

Pola w DTO spoza tabeli notyfikacji (pochodzą z `responses.get-transaction-response-200`, więc nie są wymysłem):
`refunds`, `payment` (`PaymentSummaryDto`), `blikTransactionId`, `fee`, `payerData`, `payerCardData`, `paidAmount`,
`action`, `paymentProfile` — `TransactionDto.php:32-42,65-69`. → `[INFO] N-29b`.

### Obiekt `payment` → `src/DTO/Casts/TransactionPaymentDto.php`

| Pole ze spec | Typ wg spec | Schemat walidatora | DTO repo | Status |
|---|---|---|---|---|
| `id` | string(36) UUIDv4 | **nie ma** | `'string'` (`TransactionPaymentDto.php:31`) | OK |
| `title` | string(255) | **nie ma** | brak w `$casts`, PHPDoc `:15` → raw | `[ROZBIEŻNOŚĆ] N-21` |
| `amount` | integer(9), max 999999999 | **nie ma** | `'int'` (`:32`) | OK |
| `status` | enum `new`,`pending`,`settled`,`cancelled`,`rejected` (**bez `authorized`**) | jest, enum = **wszystkie 9** case'ów `TransactionStatus` (`Validator.php:73-76`) | `TransactionStatus` (`:33`) | `[ROZBIEŻNOŚĆ] N-24` |
| `created` | integer(10) | **nie ma** | `'int'` (`:34`) | OK |
| `orderId` | string(100) | **nie ma** | `'string'` (`:35`) | OK |
| `currency` | string(3) ISO 4217 | **nie ma** | `Currency` (`:36`) | OK |
| `modified` | integer(10) | **nie ma** | `'int'` (`:37`) | OK |
| `serviceId` | string(36) UUIDv4 | **nie ma** | `'string'` (`:38`) | OK |
| `notificationUrl` | varchar(300) | **nie ma** | **brak w `$casts` i w PHPDoc** → raw | `[BRAK] N-18` |
| `ipksef` | string (ID płatności KSeF) | **nie ma** | **brak w `$casts` i w PHPDoc** → raw | `[BRAK] N-17` |

### Obiekt `action` → `src/DTO/Casts/ActionDto.php`

Walidator traktuje cały `action` jako `['type' => 'object']` (`Validator.php:79-81`) — **zero walidacji pól**.

| Pole ze spec | Typ wg spec | Schemat walidatora | DTO repo | Status |
|---|---|---|---|---|
| `type` | enum `redirect`,`transfer` | brak | `ActionType` (`ActionDto.php:28`) | OK |
| `url` | string(2083) | brak | `'string'` (`:29`) | OK |
| `method` | enum `POST`,`GET` | brak | `'string'` (`:30`) — **bez enum** | `[ROZBIEŻNOŚĆ] N-22` |
| `contentType` | varchar(100) | brak | `'string'` (`:31`) | OK |
| `contentBodyRaw` | text | brak | `'string'` (`:32`) | OK |
| `ban` | string(26), tylko przelew tradycyjny | brak | `'string'` (`:33`) | OK w DTO, ale fabryka łamie — `[BŁĄD] N-43` |
| `title` | **brak w tabeli rozdziału**, jest w `api-components.json` → `schemas.action.properties.title` | brak | PHPDoc `:19`, **brak w `$casts`** | `[INFO] N-29` |

### Obiekt `paymentProfile` → `src/DTO/Casts/PaymentProfileDto.php`

**Kluczowe: `paymentProfile` NIE jest kluczem najwyższego poziomu w `GenericNotificationDto`** (`$casts` ma tylko
`transaction`, `payment`, `action` — `GenericNotificationDto.php:27-31`). W repo `PaymentProfileDto` jest podpięte
wyłącznie *zagnieżdżone* w `TransactionDto` (`TransactionDto.php:66`) i w `ProfileResponseDto`. → `[BRAK] N-13`.

| Pole ze spec | Typ wg spec | Schemat walidatora | DTO repo | Status |
|---|---|---|---|---|
| `id` | string(36) UUIDv4 | `['type'=>'object']`, bez pól (`Validator.php:82-84`) | `$attributes` default (`PaymentProfileDto.php:33`), bez castu | OK |
| `merchantMid` | string(20) | brak | `:34` | OK |
| `merchantCustomerId` | string(36) | brak | `:35` | OK |
| `firstName` | string(100) | brak | `:36` | OK |
| `lastName` | string(100) | brak | `:37` | OK |
| `maskedNumber` | string(16) | brak | `:39` | OK |
| `month` | **string(2)** (np. `"04"`) | brak | **`'int'`** (`:49`) | `[BŁĄD] N-15` |
| `year` | **string(4)** | brak | **`'int'`** (`:50`) | `[ROZBIEŻNOŚĆ] N-15b` |
| `organization` | varchar(30) | brak | `:42` | OK |
| `isActive` | **integer(1)**, `1`/`0` | brak | **`'bool'`** (`:51`) | `[ROZBIEŻNOŚĆ] N-16` |
| `profile` | enum `oneclick`,`recurring` | brak | zwykły string, bez typu (`:44`) | `[BRAK] N-20` |
| `statusCode` | string(24) | brak | **brak w `$attributes`, `$casts` i PHPDoc** → raw | `[BRAK] N-19` |
| `statusCodeDescription` | string(113) | brak | **brak w `$attributes`, `$casts` i PHPDoc** → raw | `[BRAK] N-19` |
| `email` | brak w rozdziale, jest w `api-components.json` → `schemas.paymentProfile` | brak | `:38` | `[INFO]` |
| `firstTransactionId` | brak w rozdziale, jest w `api-components.json` | brak | `:45` | `[INFO]` |

### Ustalenia szczegółowe do struktury

**`[BŁĄD] N-11` — schemat walidacji praktycznie nic nie waliduje.**
`src/Lib/Validator.php:30-86`: brak top-levelowego `required`, brak `additionalProperties: false`,
jedyne ograniczenie to `minProperties: 1` (`:32`). Payload `{"cokolwiek": 1}` przechodzi `fromNotification()`
jako poprawny. Spec (rozdz. „Notyfikacje", lista obiektów) wymienia zamknięty zbiór czterech obiektów:
`transaction`, `payment`, `action`, `paymentProfile`. Ryzyko: `docs/notifications.md:3` sprzedaje tę metodę jako
„verification for ... schema structure", a konsument dostaje fałszywe poczucie bezpieczeństwa.

**`[ROZBIEŻNOŚĆ] N-12` — `transaction` NIE jest wymagane na górnym poziomie (to akurat zgodne ze spec),
ale `id` nie jest walidowane.** Wbrew pierwszemu wrażeniu `'required'` na `Validator.php:61-68` dotyczy
*wnętrza* obiektu `transaction`, nie top-levelu. To poprawne: spec jawnie mówi „Obiekt `payment` będzie jedynym
wysłanym obiektem, gdy link płatności straci swój czas ważności lub zostanie ręcznie anulowany", a notyfikacja
profilowa może przyjść bez transakcji. Natomiast gdy `transaction` jest obecne, walidator wymaga 6 pól,
**ale nie `id`** — a to po `id` identyfikuje się transakcję.

**`[BŁĄD] N-14` — `BaseDto` kasuje wszystkie wartości falsy z DTO, które mają `allowNull = true`.**
`src/DTO/BaseDto.php:30-32` (`array_filter($attributes)` bez callbacka) oraz `:39-41`
(`if ($this->allowNull && empty($value)) return null;`). `array_filter` bez callbacka usuwa `null`, `false`,
`0`, `0.0`, `""`, `"0"` i `[]`. Dotyczy `TransactionDto` (`:50`), `GenericNotificationDto` (`:25`),
`RefundNotificationDto` (`:24`), `FeeDto` (`:15`), `CustomerDto`, `BillingDto`, `CardDto`.
Realne skutki na payloadach ze spec:
- `transaction.title = ""` — **dokładnie ta wartość jest w przykładzie weryfikacji podpisu w `06-notyfikacje.md`** —
  znika; `$dto->transaction->title` zwraca `null` zamiast `""`.
- `transaction.statusCodeDescription = ""` — spec `13-obsluga-kodow-dla-transakcji-kartowych.md`: „W przypadku braku
  kodu z poniższej listy zwracana jest pusta wartość" — znika systemowo dla każdego niezmapowanego kodu.
- `transaction.paidAmount = 0`, `transaction.fee.merchant = 0`, `transaction.fee.surcharge = 0` — znikają.
- `transaction.statusCode` — spec: „W przypadku braku kodu z poniższej listy zwracana zostaje wartość bezpośrednio
  od dostawcy", więc surowy kod `"0"` / `"00"` od dostawcy też zniknie.
Dobra wiadomość: `ActionDto` i `TransactionPaymentDto` **nie** ustawiają `allowNull`, więc ich
`contentType: ""` / `contentBodyRaw: ""` / `payment.title: ""` przechodzą nienaruszone.

**`[BRAK] N-13` — `paymentProfile` nie jest kluczem najwyższego poziomu `GenericNotificationDto`.**
`src/DTO/Notifications/GenericNotificationDto.php:27-31` vs spec, rozdz. „Notyfikacje", lista obiektów
(„`paymentProfile` - zawiera informacje na temat utworzonego profilu kartowego Oneclick/Recurring")
oraz sekcja „Parametry dla obiektu `paymentProfile`". Dane nie giną (Fluent przechowa surową tablicę),
ale `$notification->paymentProfile` zwróci `array`, nie `PaymentProfileDto` — PHPDoc klasy (`:15-17`)
w ogóle o tym kluczu nie wspomina, więc konsument biblioteki nie ma jak się dowiedzieć, że to pole istnieje.

**`[BŁĄD] N-15` / `[ROZBIEŻNOŚĆ] N-15b` — rzutowanie `month`/`year` profilu na `int`.**
`src/DTO/Casts/PaymentProfileDto.php:49-50`. Spec (tabela `paymentProfile`): `month` to `string(2)`,
`year` to `string(4)`; `api-components.json` → `schemas.paymentProfile.properties.month/year` również `"type": "string"`.
`"04"` → `4` bezpowrotnie gubi wiodące zero; odtworzenie daty ważności karty (`04/2027`) wymaga ręcznego `str_pad`.

**`[ROZBIEŻNOŚĆ] N-16` — `isActive` rzutowane na `bool`.** `PaymentProfileDto.php:51`. Spec: `integer(1)` o wartościach
`1`/`0` (`api-components.json`: `"type": "integer", "enum": [0, 1]`). Semantycznie równoważne, ale `toArray()`
odda `true`/`false`, czyli payload po round-tripie nie jest już zgodny ze spec.

**`[NADMIAR] N-23` — `TransactionStatus` ma wartości spoza specyfikacji.**
`src/Types/TransactionStatus.php:12` (`submitted_for_settlement`) i `:17` (`refund`) — **nie występują nigdzie**
w żadnym z plików spec (zweryfikowane `grep`-em po całym katalogu spec). `:15` (`error`) nie jest w tabeli
notyfikacji, ale **jest** w `api-components.json` → `schemas.status.enum`
(`["new","pending","settled","authorized","rejected","cancelled","error"]`) — to `[ROZBIEŻNOŚĆ]` wewnątrz samej spec,
nie błąd repo. `refund` jest szczególnie mylące: w spec `refund` to wartość `transaction.type`, nie `status`;
`TransactionStatus::canChange()` (`:25-27`) utrwala to pomieszanie, dopuszczając przejście `settled → refund`.

**`[ROZBIEŻNOŚĆ] N-24` — walidator dopuszcza `payment.status: authorized`.**
`src/Lib/Validator.php:28` + `:73-76` używa pełnej listy `TransactionStatus`. Spec, tabela `payment`:
dopuszczalne wartości to wyłącznie `new`, `pending`, `settled`, `cancelled`, `rejected` — **bez `authorized`**
(w odróżnieniu od tabeli `transaction`, gdzie `authorized` jest, zgodnie z `07-preautoryzacja-transakcji.md`).

**`[ROZBIEŻNOŚĆ] N-25` — brak ograniczeń długości/zakresu w schemacie.**
`Validator.php:37-41` nie ma `maximum: 999999999` (spec: „maksymalna wartość: 999999999");
`:50-55` nie ma `maxLength: 100` ani `pattern` dla `orderId` (spec: „dopuszczalne znaki: A-Za-z0-9#_-./ oraz
znak spacji(0x20) i znaki z zakresu UNICODE 00C0 - 02C0"), ani `maxLength: 36`/`format: uuid` dla `serviceId`.

**`[ROZBIEŻNOŚĆ] N-26` — `paymentMethod`/`paymentMethodCode` jako zamknięte enumy w DTO.**
`src/DTO/Casts/TransactionDto.php:63-64` → `BaseDto::castAttribute` `:58` wykonuje `$castType::from($value)`,
co przy nieznanej wartości rzuca `\ValueError` **z konstruktora DTO**. Spec, tabela `transaction`, typuje oba pola
jako zwykły `string` („Metoda realizacji zamówienia." / „Oznaczenie kanału płatności.") bez listy dozwolonych wartości.
Stan na dziś: zweryfikowałem, że **repo pokrywa 100% wartości** z `api-components.json`
(`paymentMethod`: 8/8, `paymentMethodCode`: 48/48), więc nie ma dziś regresji — ale każdy nowy kanał włączony
przez ING po stronie bramki wysadzi parsowanie notyfikacji i, w konsekwencji, da 500 i 18 ponowień.

**`[NADMIAR] N-27`** — `src/Types/Currency.php`: `UAH`, `HRK` nie występują w `api-components.json` →
`schemas.currency.enum` (10 wartości: PLN, EUR, USD, GBP, CHF, SEK, HUF, CZK, BGN, RON). Wszystkie 10 ze spec
są w repo, więc walidacja `currency` nie odrzuci poprawnej notyfikacji.

**`[NADMIAR] N-28`** — `src/Types/PaymentMethod.php`: `lease`, `visa_mobile` spoza `schemas.paymentMethod.enum`
(`visa_mobile` istnieje w spec, ale jako `paymentMethodCode`, nie `paymentMethod`).
`src/Types/PaymentMethodCode.php`: `blik_oneclick`, `c2p`, `citi`, `envelo`, `ideabank`, `imoje_installments`,
`tmobile` spoza `schemas.paymentMethodCode.enum`.

**`[NADMIAR] N-30` — `RefundNotificationDto::$originalTransactionId` nie występuje w żadnym pliku spec.**
`src/DTO/Notifications/RefundNotificationDto.php:16,29` oraz `src/Factories/Notifications/RefundNotificationDtoFactory.php:34`.
`grep -r originalTransactionId` po całym katalogu spec: **0 trafień**. Patrz też `N-31b`.

**`[INFO] N-32` — spec jest wewnętrznie niespójna co do `paymentProfile.profile`.**
Rozdział notyfikacji: „dopuszczalne wartości: `oneclick`, `recurring`" (małe litery).
`api-components.json` → `schemas.paymentProfile.properties.profile.enum`: `["ONE_CLICK", "RECURRING"]`.
Repo nie typuje tego pola (`N-20`), więc technicznie przyjmie obie formy, ale
`src/Factories/Casts/PaymentProfileDtoFactory.php:30` wybiera arbitralnie `'ONE_CLICK'` — czyli fixture'y
są zgodne z OpenAPI, a niezgodne z rozdziałem notyfikacji, na który biblioteka powinna się orientować.

## Podpis i nagłówki

Format wg spec: `X-Imoje-Signature: merchantid={merchantid};serviceid={serviceid};signature={signature};alg={alg}`,
`alg` ∈ {`sha224`, `sha256`, `sha384`, `sha512`}, `own_signature = hash(body + service_key, alg)` **z surowego body**.

**`[INFO] N-05` — `HashMethod` pokrywa dokładnie 4 algorytmy ze spec.**
`src/Types/HashMethod.php:9-12`: `sha224`, `sha256`, `sha384`, `sha512`. Zgodne 1:1, bez braków i bez nadmiaru.

**`[INFO] N-06` — hash liczony jest z surowego body, zgodnie ze spec.**
`src/Lib/Validator.php:131` (`$request->getContent()`) → `src/Lib/Utils.php:32`
(`hash($hashMethod->value, $rawBody.$this->config->serviceKey)`). To jest poprawna implementacja punktu 3
z „Metody weryfikacji podpisu notyfikacji" — **nie** posortowany query, nie re-serializowany JSON.
Ważne, żeby tego nie „poprawić": re-serializacja (`json_encode($request->toArray())`) zawsze rozwali podpis.

**`[BŁĄD] N-01` — porównanie podpisu podatne na timing attack.**
`src/Lib/Utils.php:32`: `return $signature === hash(...)`. Operator `===` na stringach w PHP kończy porównanie
na pierwszym różnym bajcie. Powinno być `hash_equals($expected, $signature)`. To jest **jedyny mechanizm
uwierzytelniania notyfikacji** — spec nazywa go wprost „krytycznym elementem uwierzytelnienia informacji"
i dodaje „Zmiany statusów transakcji należy dokonywać tylko gdy weryfikacja podpisu przebiegła poprawnie".
Atakujący, który potrafi zmierzyć czas odpowiedzi endpointu notyfikacji, może forgeować podpis bajt po bajcie
i zmieniać statusy transakcji na `settled`. Najpoważniejsze ustalenie w tym rozdziale.

**`[BŁĄD] N-02` — nieznany lub brakujący `alg` rzuca `\ValueError`, nie `InvalidSignatureException`.**
`src/Lib/Validator.php:129`: `HashMethod::from($header['alg'] ?? '')`. `BackedEnum::from()` rzuca `\ValueError`
dla wartości spoza enuma — w tym dla `''`, czyli **gdy nagłówek `x-imoje-signature` w ogóle nie przyszedł**
(`:125` ma default `''`, więc `parse_str` zwraca pustą tablicę). Skutki:
1. Sygnatura metody deklaruje `@throws InvalidSignatureException` (`:119`) i to kłamie — konsument łapiący
   tylko tę klasę dostanie niezłapany `\ValueError` → HTTP 500.
2. Request bez nagłówka (lub z `alg=md5`) to **dokładnie profil ataku/skanu**, a biblioteka odpowiada
   wyjątkiem systemowym zamiast kontrolowaną odmową.
3. Spec: na 500 serwery ING uruchamiają pełny cykl ponowień — 3×10s, 5×5min, 5×60min, 5×360min, 5×720min,
   czyli 23 dostarczenia przez ~5 dni dla jednego zdarzenia.
Drobiazg przy okazji: sprawdzenie pustego body (`:131-134`) jest **po** `HashMethod::from`, więc pusty request
bez nagłówka nigdy nie dojdzie do czytelnego `InvalidSignatureException`.

**`[BŁĄD] N-03` — `merchantid` i `serviceid` z nagłówka są parsowane, ale nigdy nie weryfikowane.**
`src/Lib/Validator.php:124-144` wyciąga tylko `$header['alg']` (`:129`) i `$header['signature']` (`:137`).
`src/Lib/Config.php:12-13` **ma** `public readonly string $merchantId` i `$serviceId`, więc porównanie jest
na wyciągnięcie ręki. Spec wymienia oba pola jako składniki nagłówka i opisuje je jako „identyfikator klienta"
i „identyfikator sklepu". Dodatkowo `transaction.serviceId`/`payment.serviceId` z body też nie są konfrontowane
z konfiguracją (`Validator.php:53-55` sprawdza wyłącznie `type: string`). Przy wielu sklepach na jednym
`serviceKey` albo przy pomyłce konfiguracyjnej biblioteka przyjmie notyfikację cudzego sklepu bez mrugnięcia.

**`[ROZBIEŻNOŚĆ] N-04` — parsowanie nagłówka przez `str_replace(';','&')` + `parse_str`.**
`src/Lib/Validator.php:124-127`. `parse_str` to parser query-stringów, nie tego formatu, i wnosi uboczne zachowania:
URL-dekoduje klucze i wartości, zamienia `+` na spację, przy powtórzonym kluczu **wygrywa ostatni**
(`alg=sha256;alg=sha512` → `sha512`), a kropki/spacje w kluczach zamienia na `_`.
Dla formatu ze spec (4 ustalone klucze, hex w `signature`) żadne z tych zachowań nie daje dziś obejścia podpisu —
hash nadal musi się zgadzać — ale to parsowanie „przez przypadek", nie przez kontrakt, i razem z `N-03`
(brak weryfikacji pozostałych pól) oznacza, że nagłówek jest w praktyce traktowany jako para `alg`+`signature`.
Rekomendacja: jawny parser z walidacją kompletu czterech kluczy.

**`[BŁĄD] N-08` — `Utils::mockHeaders()` generuje podpis z innych bajtów niż realna notyfikacja.**
`src/Lib/Utils.php:74-75`: `json_encode($dto->toArray(), JSON_UNESCAPED_SLASHES)` — **bez `JSON_UNESCAPED_UNICODE`**.
Dla porównania `tests/Lib/ValidatorTest.php:101` używa `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
Spec jawnie dopuszcza w `title` i `orderId` „znaki z zakresu UNICODE 00C0 - 02C0 (m.in. polskie znaki diakrytyczne)",
więc dla `orderId: "Zamówienie"` `mockHeaders` policzy hash z `Zamówienie`, a realny body ING przyjdzie
(najprawdopodobniej) jako surowe UTF-8. Helper do testów integracyjnych daje więc fałszywie przechodzące
lub fałszywie oblewające scenariusze dokładnie w tym przypadku, który spec wyróżnia.

**`[BRAK] N-09` — brak jakiegokolwiek wsparcia dla whitelisty IP nadawcy.**
Spec, blok „info" na początku rozdziału, podaje pięć zakresów CIDR, z których wychodzą notyfikacje:
`5.196.116.32/28`, `51.195.95.0/28`, `54.37.185.64/28`, `54.37.185.80/28`, `147.135.151.16/28`.
W repo: zero trafień (ani stałej, ani middleware'u, ani wzmianki w `docs/notifications.md`).
To jest tania druga warstwa obrony, która w połączeniu z `N-01` miałaby realną wartość.

**`[ROZBIEŻNOŚĆ] N-07` — `createSignature()`/`buildQuery()` to mechanizm paywalla, nie notyfikacji; `buildQuery` gubi zagnieżdżenia.**
`src/Lib/Utils.php:18-25` i `:35-47` implementują podpis z `ing-pay-paywall-topics/02-autoryzacja.md`
(posortowane alfabetycznie `klucz=wartość` złączone `&`, + `;{alg}` na końcu) — to **inny** mechanizm niż
weryfikacja notyfikacji i dobrze, że są rozdzielone. Ale referencyjna funkcja `prepareData()` ze spec jest
**rekurencyjna** i serializuje zagnieżdżone tablice jako `klucz[podklucz]=wartosc`, natomiast
`buildQuery` (`:42-44`) robi płaskie `$key.'='.$value`, gdzie `$value` po `transformValues` (`:56-58`)
może już być `array` → PHP zrobi „Array to string conversion" i wstawi literał `Array`.
Pełna ocena należy do rozdziału o paywallu/autoryzacji; odnotowuję, bo `Utils` był w zakresie.

## Warianty notyfikacji

Spec wymienia następujące warianty; stan pokrycia w repo:

| Wariant | Źródło w spec | Pokrycie w repo | Status |
|---|---|---|---|
| Transakcja (`transaction` + `payment` + `action`) | rozdz. „Przykładowa zawartość BODY notyfikacji" | `GenericNotificationDto` (komplet 3 kluczy) | OK |
| Link płatności wygasły/anulowany — **sam `payment`** | blok „info": „Obiekt `payment` będzie jedynym wysłanym obiektem..." | walidator przepuszcza (brak top-level `required`), `GenericNotificationDto` filtruje puste (`BaseDto.php:30`) | OK, pokryte testem `ValidatorTest.php:39-58` |
| Preautoryzacja — status `authorized` | `07-preautoryzacja-transakcji.md` | `TransactionStatus::AUTHORIZED`; `canChange()` dopuszcza `authorized→settled` i `authorized→cancelled` | OK |
| Transakcja kartowa — `statusCode` + `statusCodeDescription` | `13-obsluga-kodow-dla-transakcji-kartowych.md` | pola tylko raw, a `statusCodeDescription: ""` jest kasowane | `[BŁĄD] N-14` |
| Profil kartowy Oneclick/Recurring — `paymentProfile` na top-levelu | rozdz. „Parametry dla obiektu `paymentProfile`" | **brak w `$casts`** | `[BRAK] N-13` |
| Akcja typu `transfer` (przelew tradycyjny, pole `ban`) | tabela `action` | DTO ma `ban`, ale fabryka generuje `iban` | `[BŁĄD] N-43` |
| **Notyfikacje do raportów** (`id`,`status`,`fileExt`,`created`,`modified`,`url`) | rozdz. „Notyfikacje do raportów" | **nic** — brak DTO, fabryki, walidacji, wzmianki w docs | `[BRAK] N-31` |
| **Notyfikacje do zwrotów** (opt-in przez tech@pay.ing.pl) | rozdz. „Notyfikacje do zwrotów" | `RefundNotificationDto` + fabryka | `[ROZBIEŻNOŚĆ] N-31b` |

**`[BRAK] N-31` — notyfikacje raportowe nie są obsłużone i są pułapką bezpieczeństwa/niezawodności.**
Spec: „WAŻNE! Notyfikacje do raportów **nie zawierają sygnatury**". Jeśli konsument skonfiguruje ten sam
endpoint co dla transakcji i pójdzie za `docs/notifications.md`, to `verifySignature()` dostanie request
bez nagłówka → `\ValueError` (`N-02`) → 500 → cykl ponowień. Biblioteka nie ma ani DTO dla tego kształtu,
ani ostrzeżenia w dokumentacji.

**`[ROZBIEŻNOŚĆ] N-31b` — `RefundNotificationDto` opisuje kształt, którego spec nie definiuje.**
Spec dla zwrotów mówi tylko tyle, że domyślnie notyfikacji nie ma i trzeba je włączyć mailem — **nie podaje
żadnej struktury payloadu**. Repo (`src/DTO/Notifications/RefundNotificationDto.php:26-30`) zakłada
`transaction` + `payment` + `originalTransactionId`, przy czym to ostatnie pole nie istnieje nigdzie w spec
(`N-30`). Nie umiem tego ani potwierdzić, ani obalić na podstawie dostarczonej specyfikacji — do weryfikacji
z realnym payloadem albo z tech@pay.ing.pl. Jeśli kształt jest poprawny, należy go udokumentować z adnotacją,
skąd pochodzi; jeśli nie — to `[BŁĄD]`.

## Odpowiedź serwisu i retry

Spec: „Wymagane jest by serwer akceptanta odpowiedział statusem `200 OK` z body `{"status":"ok"}`".
Cykl ponowień: 3×10s → 5×5min → 5×60min → 5×360min → 5×720min (łącznie 23 próby, ~5 dni).
Zatrzymanie: 200 OK **albo** wyczerpanie cyklu. Duplikaty: „pierwszą należy obsłużyć (...), natomiast
drugą (duplikat) należy **zignorować**".

**`[BRAK] N-33` — biblioteka nie wspiera wymaganej odpowiedzi w żaden sposób.**
Jedyne wystąpienie `{"status":"ok"}` w całym repo to przykład w `docs/notifications.md:25`
(`grep -rn` po `src/`, `docs/`, `tests/`). Brak stałej, brak helpera, brak gotowej `JsonResponse`,
brak middleware'u. Konsument musi to przepisać z pamięci — a pomyłka (np. `response()->noContent()`,
`204`, albo zwykłe `'ok'`) jest niewidoczna lokalnie i daje 23 ponowienia na produkcji.

**`[BRAK] N-34` — zero wsparcia dla deduplikacji/idempotencji.**
Wymóg ignorowania duplikatu jest w spec wprost. Biblioteka nie daje ani klucza idempotencji
(naturalny kandydat: `transaction.id` + `transaction.modified` + `transaction.status`), ani wzmianki w docs.
W połączeniu z agresywnym cyklem ponowień (23 próby) to realne ryzyko podwójnego wydania towaru
albo podwójnej księgowości u konsumenta biblioteki.

**`[BRAK] N-35` — polityka retry nie jest nigdzie udokumentowana dla konsumenta.**
Ani `docs/notifications.md`, ani `README.md` nie wspominają o cyklu ponowień, o tym, że 200 OK kończy cykl,
ani o tym, że *każda inna* odpowiedź (w tym 4xx) powoduje ponowienie. To istotne, bo `SchemaValidationException`
niesie kod 422 (`src/Exceptions/SchemaValidationException.php:16`) — naturalny odruch to oddać 422,
co uruchomi pełny cykl dla payloadu, który nigdy się nie naprawi.

**`[INFO] N-36`** — `InvalidSignatureException` (`src/Exceptions/InvalidSignatureException.php`) nie ustawia kodu
HTTP (domyślnie `0`), `SchemaValidationException` ustawia `422` i udostępnia `context()` do logów.
Żadna z nich nie niesie informacji, jaką odpowiedzią HTTP konsument powinien odpowiedzieć ING.

## Dokumentacja (`docs/notifications.md`)

Cały plik to 27 linii: jedno zdanie opisu i jeden przykład kontrolera.

**`[BŁĄD] N-37` — komentarz „Can throw InvalidSignatureException" (`docs/notifications.md:13`) wprowadza w błąd.**
`verifySignature()` rzuca także `\ValueError` z `HashMethod::from` (`src/Lib/Validator.php:129`, patrz `N-02`).
Przykład w docs nie ma `try/catch` ani wzmianki o handlerze, więc skopiowany 1:1 daje 500 dla requestu
bez nagłówka — czyli dla notyfikacji raportowej (`N-31`) i dla każdego skanu.

**`[ROZBIEŻNOŚĆ] N-38` — `docs/notifications.md:3` obiecuje więcej, niż kod dowozi.**
„This package provides base verification for signature, **schema structure** and mappings for responses."
Faktyczna walidacja struktury (`N-11`) przepuszcza dowolny obiekt z ≥1 właściwością; `action` i `paymentProfile`
nie są sprawdzane wcale; `transaction.id` nie jest sprawdzane. Zdanie powinno albo zostać osłabione,
albo schemat dociągnięty.

**`[BRAK] N-39` — dokumentacja milczy o wszystkim, co w spec jest oznaczone jako wymagane lub „WAŻNE".**
Brakuje: cyklu ponowień i konsekwencji nie-200 (`N-35`), obowiązku ignorowania duplikatów (`N-34`),
zakresów CIDR nadawcy (`N-09`), notyfikacji raportowych bez sygnatury (`N-31`), istnienia
`RefundNotificationDto` (`N-31b`), obiektu `paymentProfile` (`N-13`), tego że `transaction` **nie** przychodzi
zawsze (scenariusz wygasłego linku płatności), oraz zdania ze spec „Zmiany statusów transakcji należy dokonywać
tylko gdy weryfikacja podpisu przebiegła poprawnie".

**`[ROZBIEŻNOŚĆ] N-40` — przykład nie pokazuje obsługi wariantu „sam `payment`".**
`docs/notifications.md:23` robi `new GenericNotificationDto($request->toArray())`, a PHPDoc tej klasy
(`GenericNotificationDto.php:15-17`) deklaruje `@property-read TransactionDto $transaction` jako **non-nullable**.
Dla notyfikacji o wygaśnięciu linku płatności `transaction` będzie `null` (odfiltrowane na `BaseDto.php:31`),
więc PHPDoc kłamie, a `$notification->transaction->status` wybucha `Attempt to read property on null`.
`$payment` ma ten sam problem w drugą stronę (`@property-read TransactionPaymentDto $payment` — non-nullable,
a notyfikacja profilowa może go nie mieć). Tylko `$action` jest poprawnie oznaczone `|null`.

## Testy

**`[BŁĄD] N-41` — test „expects to throw InvalidSignatureException" nie testuje tego, co deklaruje.**
`tests/Lib/ValidatorTest.php:115-124` tworzy `new Request` (puste body) i ustawia nagłówek z podpisem.
Przepływ w `src/Lib/Validator.php`: `:129` `HashMethod::from('sha256')` przechodzi → `:131` `$rawBody = ''`
→ `:132-134` **rzuca `InvalidSignatureException` z gałęzi pustego body**. Porównanie podpisu (`:136-144`)
nigdy się nie wykonuje. Czyli w całym repo **nie ma testu na faktycznie niezgodny podpis** — a to jedyna
bariera bezpieczeństwa tego modułu. Poprawka na `N-01` (`hash_equals`) przeszłaby/nie przeszłaby bez zmiany
wyniku tego testu.

**`[BRAK] N-42` — brakujące przypadki testowe.**
Nie ma testu na: nieznany `alg` (`alg=md5`) ani brak nagłówka `x-imoje-signature` (`N-02`);
niezgodność `merchantid`/`serviceid` (`N-03`); notyfikację z `paymentProfile` na top-levelu (`N-13`);
`action` typu `transfer` z polem `ban` (`N-43`); notyfikację zwrotu (`RefundNotificationDto` nie jest użyty
w żadnym teście); notyfikację raportową (`N-31`); zachowanie `array_filter` dla `title: ""`
i `statusCodeDescription: ""` (`N-14`); notyfikację z `statusCode`/`statusCodeDescription` dla transakcji kartowej.

**`[BŁĄD] N-43` — `ActionDtoFactory::wireTransfer()` generuje payload niezgodny ze spec.**
`src/Factories/Casts/ActionDtoFactory.php:26-31`: ustawia `'iban' => $this->faker->iban`.
Spec, tabela `action`: pole nazywa się **`ban`** („Numer rachunku bankowego, zwracany dla metody płatności
Przelew tradycyjny"), nie `iban`. Dodatkowo stan nie zmienia `type` na `ActionType::TRANSFER` (zostaje
`redirect` z `definition()` `:18`) i nie ustawia `title`, które `api-components.json` → `schemas.action`
opisuje jako „Tytuł do przelewu tradycyjnego dla typu `transfer`". `iban` nie jest też w `ActionDto::$casts`,
więc przechodzi jako raw. Efekt: jedyny fixture dla przelewu tradycyjnego produkuje payload, jakiego ING
nigdy nie wyśle — testy oparte o niego dają fałszywą zieleń.

**`[ROZBIEŻNOŚĆ] N-44` — `Utils` jest przetestowane wybiórczo akurat w miejscach krytycznych.**
`tests/Lib/UtilsTest.php:20`: `it('verifies signature')->skip('Tested in ValidatorTest')` — a w `ValidatorTest`
(patrz `N-41`) ta ścieżka faktycznie nie jest pokryta, więc `Utils::verifySignature` **nie ma żadnego testu**.
`:71`: `it('mock headers')->todo()` — `mockHeaders` też bez testu, mimo błędu `N-08`.
`:73`: `it('checks if contains structure')->todo()`.

**`[ROZBIEŻNOŚĆ] N-45` — fabryka notyfikacji nigdy nie generuje `paymentProfile` na top-levelu.**
`src/Factories/Notifications/GenericNotificationDtoFactory.php:23` woła
`TransactionDto::factory()->withPaymentProfile()`, co umieszcza profil **wewnątrz** `transaction`
(`src/Factories/Casts/TransactionDtoFactory.php:71-76`). Spec umieszcza `paymentProfile` obok `transaction`.
Czyli nawet gdyby ktoś dodał `paymentProfile` do `$casts` (`N-13`), nie ma fixture'u, który by to pokrył.

**`[ROZBIEŻNOŚĆ] N-46` — `GenericNotificationDtoFactory::make()` nadpisuje `title` wartością `orderId`.**
`src/Factories/Notifications/GenericNotificationDtoFactory.php:42`: `$data['transaction']['title'] = $orderId;`
(uuid). Spec traktuje `title` („Tytuł zamówienia", string(255)) i `orderId` („Numer zamówienia", string(100))
jako osobne pola o różnych alfabetach dozwolonych znaków. Fixture nigdy nie przetestuje ich rozróżnienia
ani przypadku `title: ""` ze spec (`N-14`).

**`[INFO] N-47`** — `tests/Lib/ValidatorTest.php:60-113` („verifies signature") jest dobrym testem
pozytywnym i wiernie odtwarza payload oraz `service_key` z przykładu w spec. Warto go rozszerzyć
o wariant z polskimi znakami (`N-08`).

**`[INFO] N-48`** — testów nie uruchamiałem: w worktree nie ma `vendor/`, a instalacja zależności
modyfikowałaby repozytorium wbrew poleceniu. Wszystkie ustalenia pochodzą z lektury kodu
i z udokumentowanej semantyki PHP (`array_filter` bez callbacka usuwa wartości falsy;
`BackedEnum::from()` rzuca `\ValueError`; `parse_str` URL-dekoduje i nadpisuje powtórzone klucze).

## Rekomendowana kolejność napraw

1. `N-01` — `hash_equals()` w `src/Lib/Utils.php:32` (bezpieczeństwo, jedna linia).
2. `N-02` — `HashMethod::tryFrom()` + `InvalidSignatureException`, i przeniesienie sprawdzenia pustego body
   przed parsowanie `alg` (`src/Lib/Validator.php:129-134`).
3. `N-03` — weryfikacja `merchantid`/`serviceid` z `Config` (`src/Lib/Validator.php:120-145`).
4. `N-14` — zamiana `array_filter($attributes)` na filtrowanie wyłącznie `null`
   (`array_filter($attributes, fn ($v) => $v !== null)`) w `src/DTO/BaseDto.php:31` **oraz** poluzowanie
   `:39` (`empty()` → `=== null`); to jedna zmiana naprawiająca komplet pustych stringów i zer.
5. `N-41` + `N-42` — test na faktycznie niezgodny podpis, zanim cokolwiek powyżej ruszy.
6. `N-13`, `N-15`, `N-43` — kształt danych (`paymentProfile` w `$casts`, `month`/`year` jako `string`, `ban`).
7. `N-33`/`N-34`/`N-35`/`N-37`/`N-39`/`N-40` — dokumentacja i nullowalność PHPDoc.
