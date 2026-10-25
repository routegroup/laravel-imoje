# Paywall (ING Pay FRONT API)

Audyt zgodności `routegroup/laravel-imoje` ze specyfikacją ING Pay Paywall (FRONT API),
pobraną z https://bump.sh/pgw/doc/ing-pay-paywall.

- **Repo:** `/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a`, branch `agent/ing-pay-api-audit-5dd10a`, HEAD `b8fb4bf`
- **Zakres:** rozdziały 00–04, 07–15 (bez 05 Notyfikacje i 06 Zwroty — inni agenci)
- **Uwaga metodyczna:** w środowisku audytu nie było dostępnego interpretera PHP ani katalogu `vendor/`,
  więc zachowania runtime są wyprowadzone z lektury kodu i udokumentowanej semantyki PHP/Laravel
  (`array_merge_recursive`, `http_build_query`, `Illuminate\Support\Arr::query`). Zgodność algorytmu
  podpisu zweryfikowana numerycznie (patrz rozdział 02).

## Podsumowanie ilościowe

Liczone są **ustalenia** (punkty list w sekcjach „Ustalenia”), nie komórki tabel porównawczych.

| Etykieta | Liczba |
|---|---|
| `[BŁĄD]` | 12 |
| `[BRAK]` | 39 |
| `[ROZBIEŻNOŚĆ]` | 9 |
| `[NADMIAR]` | 2 |
| `[INFO]` | 32 |
| **Razem** | **94** |

**Werdykt ogólny:** mechanizm przesyłania i algorytm podpisu są poprawne **wyłącznie dla płaskiego,
skalarnego zestawu pól**. Każda funkcja ING Pay wymagająca pól zagnieżdżonych (`billing`, `shipping`,
`multipayout`) jest nie tylko niezaimplementowana, ale wręcz **niemożliwa do obejścia przez konsumenta**,
bo `Utils::buildQuery()` nie realizuje rekurencji z referencyjnej implementacji w spec.

---

## 00 — Wprowadzenie / dane do integracji

**Werdykt: ZGODNE z drobnymi rozbieżnościami konfiguracyjnymi.**

| Element spec | Spec | Repo | Status |
|---|---|---|---|
| `merchantId` | wymagany | `Config::$merchantId` | OK |
| `serviceId` | wymagany | `Config::$serviceId` | OK |
| `serviceKey` | wymagany | `Config::$serviceKey` | OK |
| Bearer token | wymagany (dla RESTful API) | `Config::$apiKey` | OK (paywall go nie używa) |
| Serwer produkcyjny | `https://paywall.pay.ing.pl` | `Environment::PRODUCTION->paywallUrl()` | OK |
| Serwer sandbox | `https://paywall.sandbox.pay.ing.pl` | `Environment::SANDBOX->paywallUrl()` | OK |

### Ustalenia

- `[INFO]` `src/Lib/Config.php:11-17` — komplet czterech danych integracyjnych z rozdziału
  *Wymagane dane do integracji* jest obecny. Paywall autoryzuje się **wyłącznie** parametrem `signature`
  (rozdz. 01, wiersz 5) — `apiKey` nie bierze udziału w ruchu paywallowym i nie jest tam używany. Poprawnie.
- `[BŁĄD]` `src/ImojeServiceProvider.php:29` — `Environment::from($config->get('services.imoje.env', Environment::PRODUCTION->value))`.
  Default z `Arr::get` zadziała **tylko gdy klucz nie istnieje**. `README.md:22-42` instruuje konsumenta,
  by ustawić `'env' => env('IMOJE_ENV')` i zostawić `IMOJE_ENV=` puste w `.env`. Wtedy klucz **istnieje**
  z wartością `''` lub `null`, default nie jest aplikowany, a `Environment::from()` rzuca
  `ValueError`/`TypeError` już na etapie bootstrapu kontenera. Poprawka: `Environment::from($config->get('services.imoje.env') ?: Environment::PRODUCTION->value)`.
- `[ROZBIEŻNOŚĆ]` `src/ImojeServiceProvider.php:35-43` — `provides()` wymienia `Config`, `Api`, `Url`, `Utils`,
  ale **nie** `Paywall`. Provider nie implementuje `DeferrableProvider`, więc `provides()` jest obecnie
  tylko informacyjne i `Paywall` rozwiązuje się przez autowiring (konstruktor przyjmuje związany `Config`).
  Funkcjonalnie działa, ale lista jest niespójna — gdyby provider kiedyś został odroczony, paywall przestałby się rozwiązywać.
- `[INFO]` `src/ImojeServiceProvider.php:19-32` — `Config` rejestrowany przez `bind`, nie `singleton`;
  nowa instancja przy każdym rozwiązaniu. Brak wpływu na poprawność.
- `[BRAK]` Brak wsparcia dla wielu sklepów (`serviceId` per sklep) — `Config` trzyma dokładnie jeden
  `serviceId`/`serviceKey`, a próba nadpisania `serviceId` per transakcja jest rozbita przez błąd
  `array_merge_recursive` (patrz rozdz. 01).

---

## 01 — Utworzenie płatności

**Werdykt: ROZBIEŻNOŚCI — mechanizm wysyłki zgodny ze spec, ale brakuje 6 z 23 pól bazowych i 3 pól modułowych; dodatkowo trzy błędy konstrukcyjne DTO.**

### 1a. Mechanizm przesyłania — rozstrzygnięcie

Spec, `01-utworzenie-platnosci.md:3`:

> „Aby utworzyć płatność w ING Pay, należy wysłać żądanie **metodą POST lub GET** na adres `{server}/payment`”

| Aspekt | Spec | Repo | Status |
|---|---|---|---|
| Metoda HTTP | POST **lub** GET | GET (HTTP 302 redirect) | `[INFO]` zgodne |
| Endpoint | `{server}/payment` | `Environment::paywallUrl()` → `https://paywall.pay.ing.pl/payment` | OK |
| Kodowanie parametrów | formularz HTML (przykład) | `Arr::query()` = `http_build_query(..., PHP_QUERY_RFC3986)` | OK |
| Kolejność parametrów | nieistotna (serwer sortuje do podpisu) | kolejność wstawiania do tablicy | OK |

### Ustalenia — mechanizm

- `[INFO]` `src/Lib/Paywall.php:18-26` — **GET jest wprost dopuszczony przez spec**, więc `redirect($url)`
  nie jest naruszeniem. To koryguje pierwotną hipotezę audytu („spec wymaga POST”) — spec wymienia obie metody równorzędnie.
  Wszystkie przykłady w spec (rozdz. 01, 10, 11, 12, 13, 14) pokazują jednak **formularz POST**, i jest to
  jedyna forma pokazana dla modułów z dużymi ładunkami.
- `[ROZBIEŻNOŚĆ]` `src/Lib/Paywall.php:18-26` — brak jakiejkolwiek alternatywy POST. Konsekwencje GET-a,
  które stają się realne dopiero przy modułach:
  - **Limit długości URL.** `invoice` (rozdz. 11/13) i `cart` (rozdz. 14) to `base64_encode(gzencode(json_encode(...), 5))`.
    Przykład zakodowanego `invoice` ze spec (`11-przelewy-tradycyjne-split-payment.md:135`) ma **~430 znaków**
    dla **jednej** pozycji faktury; po `rawurlencode` rośnie do ~560. Koszyk z kilkunastoma pozycjami plus
    `billing` + `shipping` przekroczy typowy limit 8 KB (`LimitRequestLine` Apache / `large_client_header_buffers` nginx)
    i prawie na pewno limit ~2 KB starszych proxy. Żądanie zostanie odrzucone z 414, a nie z błędem walidacji ING.
  - **Wyciek PII.** Pełny query string (imię, nazwisko, e-mail, telefon, a przy `invoice` także NIP/PESEL i adres)
    ląduje w `access_log` serwera konsumenta, w logach każdego proxy/CDN po drodze, w historii przeglądarki
    i — przy linkach wychodzących z bramki — w nagłówku `Referer`. POST-em te dane są w ciele żądania.
  - Rekomendacja: zostawić `createTransaction()` jako GET (zgodne), ale dodać wariant zwracający
    auto-submitujący formularz POST (`view` z `<input type="hidden">`), tak jak pokazuje spec.

### 1b. Kompletność pól — tabela

`TransactionDto`: deklaracja kształtu w `src/DTO/Paywall/TransactionDto.php:45-66`, `@property-read` w `:16-32`.

| Pole (spec rozdz. 01) | Wymagane | Typ/format wg spec | W `DTO/Paywall/TransactionDto` | Status |
|---|---|---|---|---|
| `serviceId` | TAK | string(36), UUID v4 | tak, auto z `Config` (`:74`) | OK |
| `merchantId` | TAK | string(20) | tak, auto z `Config` (`:75`) | OK |
| `amount` | TAK | string, grosze | tak, cast `int` (`:41`) | OK |
| `currency` | TAK | string(3) ISO 4217 | tak (`:47`), **bez castu** | `[ROZBIEŻNOŚĆ]` |
| `orderId` | TAK | string(100) | tak (`:48`) | OK |
| `customerFirstName` | TAK | string(100) | tak (`:49`) | OK |
| `customerLastName` | TAK | string(100) | tak (`:50`) | OK |
| `signature` | TAK | string `hash;alg` | tak, auto (`:78`) | OK |
| `customerEmail` | NIE | string(200) | tak (`:57`) | OK |
| `customerPhone` | NIE | string(20) | tak (`:58`) | OK |
| `orderDescription` | NIE | string(255) | tak (`:62`) | OK |
| `additionalDescription` | NIE | string | **nie** | `[BRAK]` |
| `urlSuccess` | NIE | string(300) | tak (`:59`) | OK |
| `urlFailure` | NIE | string(300) | tak (`:60`) | OK |
| `urlReturn` | NIE | string(300) | tak (`:61`) | OK |
| `urlNotification` | NIE | string(300) | **nie** | `[BRAK]` |
| `visibleMethod` | NIE | string, CSV | tak (`:64`), typ `string` | `[ROZBIEŻNOŚĆ]` |
| `validTo` | NIE | timestamp (s), min. 60 s, `NULL` = bez wygaśnięcia | tak (`:65`), typ `int` | `[BŁĄD]` (semantyka NULL) |
| `preselectMethodCode` | NIE | string(20) | **nie** | `[BRAK]` |
| `billing` | NIE | tablica (9 pól) | **nie** | `[BRAK]` |
| `shipping` | NIE | tablica (8 pól) | **nie** | `[BRAK]` |
| `isKsef` | NIE | string `1`/`0` | **nie** | `[BRAK]` |
| `invoice` (rozdz. 11/13) | NIE | string base64(gzip(json)) | **nie** | `[BRAK]` |
| `cart` (rozdz. 14) | NIE | string base64(gzip(json)) | **nie** | `[BRAK]` |
| `multipayout` (rozdz. 12) | NIE | tablica tablic | **nie** | `[BRAK]` |
| — | — | — | `customerId` w `@property-read :19` | `[NADMIAR]` |

Pola `billing` (rozdz. 01, wiersze 34–46) i `shipping` (wiersze 48–59) — żadne z 9 + 8 podpól
(`firstName`, `lastName`, `company`, `street`, `city`, `region`, `postalCode`, `countryCodeAlpha2`, `taxId`)
nie jest osiągalne z poziomu paywalla. `BillingDto` **istnieje** (`src/DTO/Casts/BillingDto.php`), ale jest
podpięty wyłącznie do DTO RESTful API (`src/DTO/Api/TransactionDto.php:59-60`).

### Ustalenia — pola i konstrukcja DTO

- `[INFO]` `src/DTO/BaseDto.php:12` — `BaseDto extends Fluent`, więc **nieudeklarowane klucze skalarne
  przechodzą na wylot** do `toArray()` i do podpisu. Praktycznie oznacza to, że `urlNotification`,
  `additionalDescription`, `preselectMethodCode`, `isKsef`, `invoice` i `cart` **da się przemycić**
  jako dodatkowe klucze konstruktora — będą policzone w podpisie i wysłane. Nie są natomiast objęte
  kształtem tablicy, PHPDoc, IDE ani PHPStan (poziom 4 wg `b8fb4bf`). Pola **tablicowe**
  (`billing`, `shipping`, `multipayout`) tej furtki nie mają — rozbijają podpis (rozdz. 02).
- `[BŁĄD]` `src/DTO/Paywall/TransactionDto.php:73-76` (i analogicznie `src/DTO/Paywall/OneClickDto.php:78-81`) —
  `array_merge_recursive()` zamiast `array_merge()` / `+`. Gdy konsument poda własny `serviceId`
  lub `merchantId` (multi-sklep), `array_merge_recursive(['serviceId' => 'A'], ['serviceId' => 'B'])`
  zwraca `['serviceId' => ['A', 'B']]` — wartość staje się **tablicą**. Skutek kaskadowy:
  podpis liczony z `serviceId=Array` (ostrzeżenie *Array to string conversion*), a w URL-u leci
  `serviceId%5B0%5D=A&serviceId%5B1%5D=B`. Transakcja odrzucona z błędem podpisu, bez czytelnej diagnostyki.
  Powinno być `array_merge([...], $attributes)` albo `$attributes + [...]`.
- `[BŁĄD]` `src/DTO/BaseDto.php:26-28` w połączeniu z `src/DTO/Paywall/TransactionDto.php:40-42` —
  `castAttributes()` iteruje po `$this->casts` i wykonuje
  `$attributes[$attributeKey] = $this->castAttribute($cast, $attributes[$attributeKey] ?? null)`.
  Przy `'amount' => 'int'` i braku `amount` w danych wejściowych do tablicy **dopisywany jest `amount => 0`**
  (`(int) null`). Ponieważ podpis jest liczony **przed** `parent::__construct()` (linia `:78` przed `:80`),
  wychodzi rozjazd: podpis bez `amount`, żądanie z `amount=0`. Konsument dostaje błąd podpisu zamiast
  komunikatu „brak wymaganego pola amount”.
- `[BŁĄD]` `src/Lib/Paywall.php:22` vs `src/Lib/Utils.php:42-45` — **asymetria w traktowaniu `null`**.
  `buildQuery()` emituje `klucz=` dla wartości `null` (konkatenacja `$key.'='.$value`), natomiast
  `Arr::query()` → `http_build_query()` **pomija** klucze o wartości `null`. Każde pole przekazane jako
  `null` trafia więc do podpisu, ale nie do żądania → podpis policzony po stronie ING się nie zgodzi.
  Dotyczy to wprost udokumentowanej funkcji ze spec (`01-utworzenie-platnosci.md:28`):
  „Przekazanie parametru `validTo` = `NULL` powoduje brak wygaśnięcia linku”. Ta funkcja jest w repo nieosiągalna.
- `[BRAK]` `src/DTO/Paywall/TransactionDto.php:68-81` — brak jakiejkolwiek walidacji pól wymaganych
  (`amount`, `currency`, `orderId`, `customerFirstName`, `customerLastName`). DTO zawsze się zbuduje,
  a błąd wyjdzie dopiero na bramce.
- `[BRAK]` Brak walidacji długości i zestawów znaków ze spec: `orderId` string(100) + `A-Za-z0-9#_-./`,
  `customerFirstName`/`customerLastName` string(100) + `A-Za-z0-9-,.`, `orderDescription` string(255) +
  `A-Za-z0-9#&_-,./`, `customerPhone` string(20) + `-+0-9`, `urlSuccess`/`urlFailure`/`urlReturn`/`urlNotification` string(300).
  Żaden z tych limitów nie jest egzekwowany.
- `[BRAK]` `src/DTO/Paywall/TransactionDto.php:65` — brak kontroli minimalnego `validTo` (60 s, `01-utworzenie-platnosci.md:28`).
- `[ROZBIEŻNOŚĆ]` `src/DTO/Paywall/TransactionDto.php:40-42` vs `src/DTO/Paywall/OneClickDto.php:42-46` —
  `OneClickDto` castuje `currency` na `Currency::class`, `TransactionDto` **nie**. Ta sama waluta zachowuje się
  inaczej w dwóch DTO tego samego pakietu: w widżecie nieznana waluta rzuca `ValueError`, w paywallu przechodzi jako string.
- `[NADMIAR]` `src/DTO/Paywall/TransactionDto.php:19` — `@property-read string $customerId`. Rozdz. 01 **nie wymienia**
  `customerId` wśród parametrów `/payment`; to pole widżetu (`data-customer-id`, rozdz. 08). Mylący PHPDoc —
  pole nie występuje też w kształcie tablicy `:45-66`.
- `[INFO]` `src/Lib/Paywall.php:22` — `Arr::query()` koduje w RFC 3986 (`rawurlencode`), więc spacja → `%20`,
  `/` → `%2F`. Podpis liczony jest z wartości **surowych**, a serwer dekoduje query przed weryfikacją —
  to jest spójne i zgodne z przykładem formularzowym w spec.
- `[INFO]` `01-utworzenie-platnosci.md:78-79` — automatyczne wygaśnięcie nieopłaconego linku po 15 miesiącach.
  Czysto informacyjne, brak implikacji dla kodu.

---

## 02 — Autoryzacja (podpis)

**Werdykt: ROZBIEŻNOŚCI — algorytm odtworzony poprawnie dla danych płaskich, całkowicie niezgodny dla zagnieżdżonych.**

Referencja: `02-autoryzacja.md:33-51` (`prepareData`) i `:20-25` (`createSignature`).
Implementacja: `src/Lib/Utils.php:18-25` (`createSignature`), `:35-47` (`buildQuery`), `:49-68` (`transformValues`).

| Krok spec | Treść | Repo | Status |
|---|---|---|---|
| 1 | `ksort($data)` — sort rosnąco po kluczach | `src/Lib/Utils.php:37` `ksort($orderData)` (flagi domyślne, jak w spec) | OK |
| 2 | sklejenie `k1=v1&k2=v2...` | `src/Lib/Utils.php:42-46` | OK (tylko płaskie) |
| 2′ | **rekurencja dla tablic**: `prepareData($value, $key)`, klucz dziecka jako `rodzic[dziecko]`, ponowny `ksort` na każdym poziomie | **brak** | `[BŁĄD]` |
| 3 | `hash($alg, $body . $serviceKey)` | `src/Lib/Utils.php:22` | OK |
| 4 | dopisanie `';' . $alg` | `src/Lib/Utils.php:24` `"{$hash};{$hashMethod->value}"` | OK |
| Algorytmy | `sha224`, `sha256`, `sha384`, `sha512` | `src/Types/HashMethod.php:9-12` — komplet | OK |

**Weryfikacja numeryczna kroków 3–4.** Dla `['bar' => 'baz', 'foo' => 'faa']` i `serviceKey = '$service_key$'`
(z `tests/TestCase.php:36-42`): `sha256('bar=baz&foo=faa' . '$service_key$')` =
`df8bdc2e4d0d93be22222ea57b5b0e196b897629e9089f05baadffee96baa90d`, czyli dokładnie wartość oczekiwana
w `tests/Lib/UtilsTest.php:18`. Odwrotna kolejność konkatenacji (`serviceKey . body`) daje inny hash —
potwierdza, że repo trzyma kolejność `body + serviceKey` wymaganą przez spec.

### Ustalenia

- `[BŁĄD]` `src/Lib/Utils.php:35-47` — **`buildQuery()` nie realizuje rekurencji z `prepareData()`**.
  Pętla `foreach` robi bezwarunkowo `$data[] = $key.'='.$value;` — gdy `$value` jest tablicą, PHP 8 emituje
  warning *Array to string conversion* i wstawia literał `Array`. Dla przykładu z rozdz. 12 spec oczekuje
  `multipayout[0][amount]=100&multipayout[0][ban]=72105000028166973380325415&multipayout[0][label]=Nazwa firmy 0&multipayout[1][...]`,
  a repo wyprodukuje `multipayout=Array`. **Podpis jest cicho fałszywy** (warning, nie wyjątek), więc błąd ujawni się
  dopiero jako odrzucenie na bramce. Dotyczy `billing`, `shipping` (rozdz. 01) i `multipayout` (rozdz. 12).
- `[BŁĄD]` `src/Lib/Utils.php:49-68` — `transformValues()` też jest jednopoziomowe. Konwersja `Arrayable → array`
  (`:56-58`) i `BackedEnum → ->value` (`:60-62`) działa tylko na najwyższym poziomie. Nawet po naprawieniu
  rekurencji w `buildQuery()`, enum albo `Arrayable` zagnieżdżone w `billing`/`multipayout` nadal nie zostaną
  zserializowane. Co gorsza, `:56-58` **zamienia `Arrayable` na tablicę**, czyli aktywnie tworzy wartość,
  której `buildQuery()` nie potrafi obsłużyć — oba miejsca współgrają na niekorzyść.
- `[BRAK]` `tests/Lib/UtilsTest.php:22-27` — jedyny test `buildQuery()` używa dwóch płaskich stringów.
  Brak przypadku z tablicą zagnieżdżoną, mimo że spec podaje gotowe wektory testowe:
  `12-multiwyplaty.md:37` (`...;sha256` dla pełnego `multipayout`) i `02-autoryzacja.md:57-72`.
  To jest powód, dla którego `[BŁĄD]` powyżej nie został wychwycony.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Paywall/TransactionDto.php:78` — podpis liczony jest z **surowych atrybutów wejściowych**,
  przed `castAttributes()`. Przy obecnym zestawie castów to zazwyczaj nieszkodliwe (`(int) '100' === 100`,
  a `transformValues` i tak rozpakuje enum), ale czyni podpis zależnym od reprezentacji wejściowej,
  a nie od tego, co faktycznie zostanie wysłane. Źródło błędów opisanych w rozdz. 01 (`amount => 0`).
- `[INFO]` `src/Lib/Utils.php:27-33` `verifySignature()` liczy hash z **surowego ciała** żądania, nie z `buildQuery()` —
  to ścieżka notyfikacji (rozdz. 05, poza zakresem). Nie jest używana przy tworzeniu płatności.
- `[INFO]` `02-autoryzacja.md:33-51` — referencyjny `prepareData()` nie escapuje wartości, więc pole zawierające
  `&` lub `=` (a spec dopuszcza `&` w `orderDescription`, `01-utworzenie-platnosci.md:21`) jest w podpisie
  niejednoznaczne. Repo powiela tę samą charakterystykę, czyli jest **zgodne z referencją** — nie zgłaszam jako błąd.

---

## 03 — Język bramki płatności ING Pay

**Werdykt: ROZBIEŻNOŚCI — konstrukcja URL poprawna, jedna wartość enuma błędna.**

| Element | Spec (`03-jezyk...md:5-18`) | Repo | Status |
|---|---|---|---|
| URL bez języka | `https://paywall.pay.ing.pl/payment` | `src/Types/Environment.php:29-32` | OK |
| URL z językiem | `https://paywall.pay.ing.pl/[lang]/payment` | `src/Types/Environment.php:21-27` | OK |
| Sandbox | analogicznie `paywall.sandbox.pay.ing.pl` | `src/Types/Environment.php:25,31` | OK |
| Przekazanie języka | — | `Paywall::createTransaction($dto, ?Lang $lang)` (`src/Lib/Paywall.php:18-23`) | OK |
| Lista języków | `pl en cs de es fr it lt ru sk sl uk nl hu ro` (15) | `src/Types/Lang.php:7-21` (15, ale `ua` zamiast `uk`) | `[BŁĄD]` |

### Ustalenia

- `[BŁĄD]` `src/Types/Lang.php:18` — `case UA = 'ua';`. Spec (`03-jezyk-bramki-platnosci-ing-pay.md:18`) wymienia
  kod **`uk`** (ukraiński wg ISO 639-1). Potwierdzenie krzyżowe: `api-components.json`, schemat `locale`,
  zawiera `"uk"`; ciąg `"ua"` **nie występuje w żadnej z dwóch specyfikacji** (paywall ani RESTful API).
  Skutki: (a) `Lang::UA` generuje `https://paywall.pay.ing.pl/ua/payment` — ścieżka nieudokumentowana,
  bramka najpewniej odpadnie na fallback `en` lub zwróci 404; (b) ukraińskiego **nie da się** ustawić, bo
  `Lang::UK` nie istnieje; (c) ten sam enum jest castem `locale` w `src/DTO/Casts/CustomerDto.php:43`,
  więc błąd propaguje się na RESTful API, gdzie `locale` ma twardy `enum` w schemacie i zostanie odrzucone.
- `[BRAK]` `src/Types/Lang.php:7-21` — brak `bg` i `sv`. Dla ścieżki URL paywalla to poprawne (rozdz. 03 ich nie wymienia),
  ale `data-locale` widżetu (`08-widzet...md:56`) oraz `locale` w RESTful API dopuszczają 17 wartości.
  Enum nie da się użyć w tych dwóch kontekstach bez luk.
- `[INFO]` `src/Types/Environment.php:20-33` — gdy `$lang === null`, używany jest URL bazowy, co zgodnie ze spec
  włącza autodetekcję języka z ustawień przeglądarki. Zachowanie domyślne poprawne.
- `[BRAK]` `tests/Lib/PaywallTest.php` — brak testu dla wariantu z językiem; `UrlTest.php` pokrywa wyłącznie
  adresy RESTful API (`:11` `$base = 'https://api.sandbox.pay.ing.pl/v1/...'`), żadnego paywallowego.

---

## 04 — Wyświetlanie wybranych metod płatności na bramce

**Werdykt: ROZBIEŻNOŚCI — wartości są dostępne, ale nie są wpięte w DTO paywalla; brak `preselectMethodCode`.**

| Metoda (spec `04-...md:5-14`) | Wartość | W `src/Types/PaymentMethod.php` | Status |
|---|---|---|---|
| Karty płatnicze | `card` | `:9` `CARD` | OK |
| Portfele elektroniczne | `wallet` | `:16` `WALLET` | OK |
| Przelew online | `pbl` | `:10` `PAY_BY_LINK` | OK |
| BLIK | `blik` | `:11` `BLIK` | OK |
| Płatności odroczone | `imoje_paylater` | `:12` `PAYLATER` | OK |
| Leasing online | `lease` | `:13` `LEASE` | OK |
| Przelew tradycyjny | `wt` | `:14` `WIRE_TRANSFER` | OK |
| Raty | `imoje_installments` | `:18` `IMOJE_INSTALLMENTS` | OK |
| — | — | `:15` `ING = 'ing'` | `[NADMIAR]` |
| — | — | `:17` `VISA_MOBILE = 'visa_mobile'` | `[NADMIAR]` |

### Ustalenia

- `[ROZBIEŻNOŚĆ]` `src/DTO/Paywall/TransactionDto.php:63-64` — `visibleMethod` zadeklarowane jako goły `string`
  z komentarzem `// Separated by comma`. Enum `PaymentMethod` **nie jest w żaden sposób podpięty** do DTO
  paywalla: brak castu, brak akceptacji `PaymentMethod[]`, brak helpera składającego CSV. Konsument musi
  ręcznie robić `implode(',', array_column($methods, 'value'))`, a literówka w stringu przejdzie bez ostrzeżenia.
  To niweczy sens posiadania enuma w pakiecie reklamującym się „typed objects” (`README.md:10`).
- `[BŁĄD]` Konsekwencja powyższego: naturalna próba `'visibleMethod' => [PaymentMethod::CARD, PaymentMethod::BLIK]`
  przekazuje **tablicę**, którą `Utils::buildQuery()` (`src/Lib/Utils.php:42-45`) zamienia na `visibleMethod=Array`.
  Cichy, błędny podpis — ten sam mechanizm co w rozdz. 02.
- `[BRAK]` `src/DTO/Paywall/TransactionDto.php:45-66` — brak `preselectMethodCode` (`01-utworzenie-platnosci.md:29`,
  string(20), kanał domyślnie zaznaczony po otwarciu bramki). Enum `src/Types/PaymentMethodCode.php:9-65`
  zawiera komplet kodów kanałów, ale nie jest wykorzystany po stronie paywalla — tylko w `src/DTO/Api/PaymentDto.php:24,58`.
  Spec zaznacza dodatkowo: „Pole nie może być zdefiniowane i pozostawione bez wartości”, co w obecnym
  kodzie i tak byłoby złamane przez asymetrię `null` opisaną w rozdz. 01.
- `[NADMIAR]` `src/Types/PaymentMethod.php:15,17` — `ING` i `VISA_MOBILE` nie figurują w tabeli dopuszczalnych
  wartości `visibleMethod`. Wg rozdz. 04 Visa Mobile jest podkategorią `wallet`. Użycie ich jako `visibleMethod`
  jest poza specyfikacją. (Pełny audyt enumów — inny agent; zgłaszam wyłącznie w kontekście paywalla.)
- `[INFO]` `04-...md:16-17` — ostrzeżenie o webhookach PayPal (nie usuwać adresów URL, aktualizować klucze
  integracyjne w panelu). Dotyczy konfiguracji w panelu ING, nie kodu pakietu.

---

## 07 — Płatności odroczone

**Werdykt: ZGODNE — moduł nie wymaga odrębnych pól, obsługiwany przez istniejący `visibleMethod`.**

| Wymaganie spec (`07-...md:3-6`) | Repo | Status |
|---|---|---|
| Brak odrębnej integracji; standardowe `/payment` | `src/Lib/Paywall.php:18-26` | OK |
| `visibleMethod` = `imoje_paylater` dla wyodrębnienia metody | `src/Types/PaymentMethod.php:12` `PAYLATER = 'imoje_paylater'` | OK |

### Ustalenia

- `[INFO]` To jedyny moduł z zakresu audytu, który działa „z pudełka” — nie wnosi żadnego dodatkowego pola
  do żądania. Jedyne zastrzeżenie to brak typowania `visibleMethod` (rozdz. 04).
- `[INFO]` `04-...md:11` wymienia dostawców pod `imoje_paylater`: Twisto, PayPo, PragmaGO, Blik płacę później.
  Odpowiadające im kody kanałów są w `src/Types/PaymentMethodCode.php:40,42,50,13`
  (`imoje_twisto`, `paypo`, `pragma_go`, `blik_paylater`) — przydatne dopiero gdy powstanie `preselectMethodCode`.

---

## 08 — Widżet dla płatności kartami

**Werdykt: ROZBIEŻNOŚCI — adresy i podstawowe pola zgodne, ale brak warstwy renderującej i 6 parametrów.**

| Parametr spec | Wymagany | `OneClickDto` (`src/DTO/Paywall/OneClickDto.php`) | Status |
|---|---|---|---|
| `data-merchant-id` | TAK | `merchantId`, auto `:80` | OK |
| `data-service-id` | TAK | `serviceId`, auto `:79` | OK |
| `data-amount` | TAK | `amount` `:51`, cast `int` `:43` | OK |
| `data-currency` | TAK | `currency` `:52`, cast `Currency` `:44` | OK |
| `data-order-id` | TAK | `orderId` `:53` | OK |
| `data-customer-id` | TAK | `customerId` `:54` | OK |
| `data-customer-first-name` | TAK | `customerFirstName` `:55` | OK |
| `data-customer-last-name` | TAK | `customerLastName` `:56` | OK |
| `data-customer-email` | TAK | `customerEmail` `:57` | OK |
| `data-signature` | TAK | `signature`, auto `:87` | OK |
| `data-customer-phone` | NIE | `customerPhone` `:64` | OK |
| `data-order-description` | NIE | `orderDescription` `:65` | OK |
| `data-url-success` | NIE | `urlSuccess` `:66` | OK |
| `data-url-failure` | NIE | `urlFailure` `:67` | OK |
| `data-url-return` | NIE | `urlReturn` `:68` | OK |
| `data-url-cancel` | NIE | `urlCancel` `:69` | OK |
| `data-widget-type` | NIE | `widgetType` `:61`, default `oneclick` `:83-85` | OK |
| `data-valid-to` | NIE | `validTo` `:70` | OK |
| `data-invoice` | NIE | **nie** | `[BRAK]` |
| `data-multipayout` | NIE | **nie** | `[BRAK]` |
| `data-locale` (konfig.) | NIE | **nie** | `[BRAK]` |
| `data-inline` (konfig.) | NIE | **nie** | `[BRAK]` |
| `data-element-id` (konfig.) | NIE | **nie** | `[BRAK]` |
| `data-element-event` (konfig.) | NIE | **nie** | `[BRAK]` |

| Adres skryptu | Spec (`08-...md:12,18`) | `src/Types/Environment.php:35-41` | Status |
|---|---|---|---|
| Produkcja | `https://paywall.pay.ing.pl/js/widget.min.js` | identycznie `:38` | OK |
| Sandbox | `https://paywall.sandbox.pay.ing.pl/js/widget.min.js` | identycznie `:39` | OK |

### Ustalenia

- `[BRAK]` **Brak warstwy renderującej widżet.** Spec wymaga znacznika `<script>` z `id="imoje-widget__script"`
  (`08-...md:84`: „ID skryptu. Zawsze musi mieć wartość `imoje-widget__script`”) i atrybutami
  `data-kebab-case`. Repo oddaje konsumentowi `OneClickDto` z kluczami `camelCase` (`docs/paywall.md:70-73`)
  i **nie dostarcza** ani widoku Blade, ani metody `toDataAttributes()`, ani mapowania
  `customerFirstName → data-customer-first-name`. Konsument musi sam napisać tę konwersję, a każda pomyłka
  w niej skutkuje błędem podpisu (nazwy w podpisie i nazwy atrybutów muszą odpowiadać sobie wg `08-...md:91-95`).
  To jest sprzeczne z deklaracją `docs/paywall.md:46`: „The package helps you quickly apply the widget form on your page”.
- `[BRAK]` `src/Types/Environment.php:35-41` — brak stałej dla `id="imoje-widget__script"` oraz dla
  kontenera `imoje-widget__wrapper` (`08-...md:57`), mimo że obie wartości są przez spec narzucone sztywno.
- `[INFO]` `src/DTO/Paywall/OneClickDto.php:83-87` — `widgetType` jest ustawiany **przed** liczeniem podpisu,
  czyli wchodzi do sygnatury. To zgodne ze spec: `data-widget-type` należy do tabeli parametrów
  podpisywanych (`08-...md:41`), nie do tabeli parametrów konfiguracyjnych (`08-...md:54-59`).
- `[INFO]` `src/Types/WidgetType.php:9-11` — `oneclick`, `recurring`, `ecom3ds`: komplet wartości z `08-...md:41`.
- `[ROZBIEŻNOŚĆ]` `08-...md:52,87` — spec wymaga **wykluczenia** z podpisu parametrów `src`, `id`,
  `data-locale`, `data-inline`, `data-element-id`, `data-element-event`. Repo nie ma żadnego mechanizmu
  wykluczania (`src/Lib/Utils.php:35-47` podpisuje wszystko, co dostanie). Obecnie wychodzi to na zero,
  bo tych pól po prostu nie ma w DTO — ale w momencie dodania `data-locale` (patrz `[BRAK]` wyżej)
  naiwne wrzucenie go do `OneClickDto` **złamie podpis**. Wymaga to listy wykluczeń, nie tylko nowych pól.
- `[BŁĄD]` `src/DTO/Paywall/OneClickDto.php:78` — ten sam błąd `array_merge_recursive` co w `TransactionDto`
  (patrz rozdz. 01).
- `[BRAK]` `tests/` — **brak jakiegokolwiek testu `OneClickDto`**. Nie ma `tests/DTO/` ani odpowiednika
  `PaywallTest` dla widżetu; `OneClickDtoFactory` (`src/Factories/Paywall/OneClickDtoFactory.php`) nie jest nigdzie użyta.
- `[BRAK]` `data-multipayout` (`08-...md:44,49`) — format JSON inline
  (`[{"ban":"...","label":"...","amount":100}]`), odmienny od formy `multipayout[0][ban]` dla `/payment` (rozdz. 12).
  Repo nie obsługuje żadnej z tych dwóch form.

---

## 09 — Płatności kartowe OneClick i rekurencyjne

**Werdykt: ZGODNE — zakres FRONT API ogranicza się do utworzenia profilu widżetem, co repo pokrywa.**

| Wymaganie spec (`09-...md:3-7`) | Repo | Status |
|---|---|---|
| FRONT API pozwala **tylko** utworzyć profil kartowy przez widżet | `OneClickDto` + `Environment::widgetUrl()` | OK |
| Obciążenie / dezaktywacja profilu → RESTful API | `src/Lib/Url.php:66-72` (`/transaction/profile`), `:74-84` (`/profile/id/{id}`), `:125-134` (`/profile/deactivate`), `:113-123` (`/profile/cid/{cid}`) | OK (poza zakresem) |
| Typy: `oneclick`, `recurring`, `ecom3ds` | `src/Types/WidgetType.php:9-11` | OK |

### Ustalenia

- `[INFO]` Rozdział nie wnosi żadnych nowych pól do FRONT API. Jedyne praktyczne zastrzeżenie to brak
  warstwy renderującej widżet (rozdz. 08) — bez niej „utworzenie profilu” pozostaje do napisania po stronie konsumenta.
- `[INFO]` `src/DTO/Paywall/OneClickDto.php:83-85` — domyślny `widgetType` to `oneclick`. Dla płatności
  rekurencyjnej lub zwykłej 3DS konsument musi go nadpisać jawnie; `docs/paywall.md:60-68` tego nie wspomina.

---

## 10 — Raty

**Werdykt: NIEZAIMPLEMENTOWANE (poza samą wartością `visibleMethod`).**

| Element spec | Repo | Status |
|---|---|---|
| `visibleMethod` = `imoje_installments` (`10-raty.md:15,19`) | `src/Types/PaymentMethod.php:18` | OK |
| Widżet kalkulatora rat — URL produkcja `https://paywall.pay.ing.pl/js/installments.js` (`:50`) | **brak** | `[BRAK]` |
| Widżet kalkulatora rat — URL sandbox `https://paywall.sandbox.pay.ing.pl/js/installments.js` (`:52`) | **brak** | `[BRAK]` |
| `id` skryptu `imoje-installments__script` (`:56`) | **brak** | `[BRAK]` |
| Kontener `imoje-installments__wrapper` (`:46`) | **brak** | `[BRAK]` |
| Parametry kalkulatora: `merchantId`, `serviceId`, `amount`, `currency`, `signature` (`:60-66`) | **brak DTO** | `[BRAK]` |
| Odbiór `channel` + `period` z `window.message` (`:108-115`) i przekazanie do transakcji | **brak** | `[BRAK]` |

### Ustalenia

- `[BRAK]` `src/Types/Environment.php:35-46` — klasa ma `widgetUrl()` i `cdnUrl()`, ale **nie ma**
  `installmentsUrl()`. Żeby dodać moduł rat trzeba: `installmentsUrl()` zwracające
  `https://paywall.pay.ing.pl/js/installments.js` / `https://paywall.sandbox.pay.ing.pl/js/installments.js`.
- `[BRAK]` Brak DTO kalkulatora rat. Minimalny zestaw do dodania (`10-raty.md:60-66`):
  `merchantId`, `serviceId`, `amount`, `currency`, `signature` — czyli nowe `DTO/Paywall/InstallmentsDto`
  z auto-podpisem analogicznym do `TransactionDto:78`.
- `[BRAK]` Brak walidacji progów: `amount` ∈ `<30000, 5000000>` groszy (`:64`), `currency` wyłącznie `PLN` (`:65`),
  kwota zamówienia 300–50 000 PLN (`:13`).
- `[BRAK]` Brak pól `channel` i `period` w `DTO/Paywall/TransactionDto` — spec (`:44,104-115`) opisuje
  pobranie ich z kalkulatora i przekazanie do zapytania tworzącego transakcję.
- `[INFO]` `10-raty.md:50-52` vs `:81` — niespójność **w samej specyfikacji**: sekcja „Adresy URL widżetu”
  podaje `https://paywall.pay.ing.pl/js/installments.js`, a przykład kodu tuż poniżej używa starego hosta
  `https://paywall.imoje.pl/js/installments.js`. Przy implementacji należy iść za sekcją adresów (`pay.ing.pl`),
  zgodnie z migracją hostów z commita `b6821f4`.
- `[INFO]` `10-raty.md:117-121` — zwroty do rat realizuje się przez `can-refund` w RESTful API;
  `src/Lib/Url.php:104-111` `createCanRefundUrl()` istnieje (zakres innego agenta).
- `[BRAK]` `docs/paywall.md` — moduł rat nie jest w dokumentacji konsumenckiej wspomniany **ani słowem**,
  nawet jako „Not implemented” (w odróżnieniu od multiwypłat, księgowości i leasingu, `docs/paywall.md:77-87`).

---

## 11 — Przelewy tradycyjne Split Payment

**Werdykt: NIEZAIMPLEMENTOWANE.**

| Element spec | Repo | Status |
|---|---|---|
| `visibleMethod` = `wt` (`11-...md:117,150`) | `src/Types/PaymentMethod.php:14` | OK |
| Pole `invoice` w `/payment` | **brak w `DTO/Paywall/TransactionDto`** | `[BRAK]` |
| Obiekt `buyer` — 10 pól (`:7-19`) | **brak** | `[BRAK]` |
| Tablica `positions` — 8 pól (`:23-32`) | **brak** | `[BRAK]` |
| Enum `taxStake` — 11 wartości (`:30`) | **brak** | `[BRAK]` |
| Enum `type` = `PERSON` / `COMPANY` (`:10`) | **brak** | `[BRAK]` |
| Enum `idType` = `ID` / `VAT_ID` (`:18`) | **brak** | `[BRAK]` |
| Koder `base64_encode(gzencode(json_encode($invoiceData), 5))` (`:71`) | **brak** | `[BRAK]` |

### Ustalenia

- `[BRAK]` Pełna lista pól do dodania, `buyer` (`11-...md:7-19`): `invoiceId` (TAK), `type` (TAK, `PERSON`|`COMPANY`),
  `email` (TAK, string 200), `fullName` (TAK, string 200), `street` (TAK, string 200), `city` (TAK, string 100),
  `postalCode` (TAK, string 30), `countryCodeAlpha2` (TAK, string 2), `idCountryCodeAlpha2` (NIE, wymagane dla `idType=VAT_ID`),
  `idType` (NIE, wymagane dla `type=COMPANY`), `idNumber` (NIE, string 30, wymagane dla `type=COMPANY`).
- `[BRAK]` `positions` (`11-...md:23-32`): `name` (TAK), `code` (TAK), `quantity` (TAK, min. 0), `unit` (TAK),
  `grossAmount` (TAK, integer), `taxStake` (TAK, string lub number), `taxAmount` (TAK, integer, grosze),
  `discountAmount` (NIE). **Uwaga:** `taxAmount` jest wymagany w split payment (rozdz. 11), a **nie występuje**
  w wariancie ING Księgowość (rozdz. 13) — to dwa różne kształty obiektu `invoice` pod tą samą nazwą pola.
- `[BRAK]` `taxStake` — wartości: `TAX_23`, `TAX_22`, `TAX_8`, `TAX_7`, `TAX_5`, `TAX_3`, `TAX_0`, `TAX_EXEMPT`,
  `TAX_NOT_LIABLE`, `TAX_REVERSE_CHARGE`, `TAX_EXCLUDING`; dopuszczalny też wariant numeryczny (`23`).
  Brak odpowiadającego enuma w `src/Types/`.
- `[BRAK]` Brak helpera kodującego. Potrzebne: metoda typu `Utils::encodeInvoice(array $data): string`
  implementująca `base64_encode(gzencode(json_encode($data), 5))` (`11-...md:71`) — poziom kompresji **5** jest
  w spec podany wprost.
- `[INFO]` Ponieważ `invoice` jest po zakodowaniu **stringiem**, a `BaseDto extends Fluent`
  (`src/DTO/BaseDto.php:12`), konsument może go dziś przemycić jako dodatkowy klucz konstruktora
  `TransactionDto` — trafi do podpisu i do query. To obejście działa, ale jest nieudokumentowane,
  nietypowane i wymaga ręcznego napisania kodera oraz walidacji po stronie konsumenta.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Api/TransactionDto.php:64` zawiera `// 'invoice' => 'array' @todo` — po stronie
  RESTful API pole jest przynajmniej odnotowane w kształcie (`:90`), po stronie paywalla nie ma po nim śladu.
  Asymetria między dwiema integracjami tego samego dostawcy.
- `[INFO]` `11-...md:65` — parametry `invoice` **muszą** wejść do podpisu. Po zakodowaniu to pojedynczy string,
  więc obecny `buildQuery()` poradziłby sobie poprawnie (w odróżnieniu od `multipayout`).

---

## 12 — Multiwypłaty

**Werdykt: NIEZAIMPLEMENTOWANE — i jedyny moduł, którego nie da się obejść bez poprawki w `Utils`.**

| Element spec | Repo | Status |
|---|---|---|
| Tablica `multipayout` w `/payment` | **brak** | `[BRAK]` |
| `ban` (TAK, string) | **brak** | `[BRAK]` |
| `amount` (TAK, integer, grosze) | **brak** | `[BRAK]` |
| `label` (TAK, string 70) | **brak** | `[BRAK]` |
| `title` (NIE, string 105, wszystko-albo-nic) | **brak** | `[BRAK]` |
| Indeksowanie od 0, format `multipayout[0][ban]` (`12-...md:12,31-36`) | **brak** | `[BRAK]` |
| Uwzględnienie w podpisie (`12-...md:72`) | **niemożliwe** — patrz niżej | `[BŁĄD]` |

### Ustalenia

- `[BŁĄD]` `src/Lib/Utils.php:42-45` — nawet ręczne przekazanie `'multipayout' => [['ban'=>..., 'amount'=>..., 'label'=>...]]`
  do `TransactionDto` **nie zadziała**: `buildQuery()` wyprodukuje `multipayout=Array` zamiast oczekiwanego przez spec
  `multipayout[0][amount]=100&multipayout[0][ban]=72105000028166973380325415&multipayout[0][label]=Nazwa firmy 0&...`.
  To jedyny moduł z zakresu audytu, dla którego **nie ma obejścia po stronie konsumenta** — wymaga zmiany
  w `Utils::buildQuery()` (implementacja rekurencji z `02-autoryzacja.md:33-51`).
  Spec dostarcza gotowy wektor testowy: `12-multiwyplaty.md:37`
  (`1a466af99a18c4691576bbbf5b935e2ac082285ae28a88f8686ac3317836a6f5;sha256`) dla danych z `:43-69`.
- `[BRAK]` Pola do dodania w `DTO/Paywall/TransactionDto`: `multipayout` jako `list<array{ban: string, amount: int, label: string, title?: string}>`,
  plus reguła „podanie `title` dla jednego elementu wymusza podanie go dla pozostałych” (`12-...md:10`).
- `[BRAK]` Brak walidacji długości `label` (70) i `title` (105) oraz zestawów znaków (`12-...md:9-10`).
- `[INFO]` `src/Lib/Paywall.php:22` — sama warstwa transportowa by sobie poradziła: `Arr::query()`
  (`http_build_query`) poprawnie serializuje zagnieżdżenia do `multipayout%5B0%5D%5Bban%5D=...`,
  co po zdekodowaniu daje format oczekiwany przez spec. Wąskim gardłem jest **wyłącznie** podpis.
- `[INFO]` `docs/paywall.md:77-79` uczciwie deklaruje „Multipayout — Not implemented.” Zgodne ze stanem kodu.
- `[ROZBIEŻNOŚĆ]` `src/DTO/Api/TransactionDto.php:41,63,89` — po stronie RESTful API `multipayout` jest
  co najmniej zadeklarowany w kształcie (`multipayout?: array<mixed>`) z `@todo` na cast; po stronie paywalla brak.

---

## 13 — ING Księgowość

**Werdykt: NIEZAIMPLEMENTOWANE.**

| Element spec | Repo | Status |
|---|---|---|
| Pole `invoice` w `/payment` | **brak** | `[BRAK]` |
| `buyer` — 9 pól (`13-...md:7-18`) | **brak** | `[BRAK]` |
| `positions` — 7 pól (`13-...md:22-30`) | **brak** | `[BRAK]` |
| `basisForVatExemption.type` — 19 wartości (`13-...md:36`) | **brak** | `[BRAK]` |
| `basisForVatExemption.text` (`13-...md:37`) | **brak** | `[BRAK]` |
| `issueInvoice` (boolean, `13-...md:44`) | **brak** | `[BRAK]` |
| `isKsef` (rozdz. 01, `1`/`0`) | **brak** | `[BRAK]` |
| Koder `base64_encode(gzencode(json_encode(...), 5))` (`13-...md:80`) | **brak** | `[BRAK]` |

### Ustalenia

- `[BRAK]` `buyer` (`13-...md:7-18`): `type` (TAK, `PERSON`|`COMPANY`), `email` (TAK, 200), `fullName` (TAK, 200),
  `street` (TAK, 200), `city` (TAK, 100), `postalCode` (TAK, 30), `countryCodeAlpha2` (TAK, 2),
  `idCountryCodeAlpha2` (NIE), `idType` (NIE), `idNumber` (NIE, 30).
  **Różnica wobec rozdz. 11:** brak `invoiceId` — w ING Księgowość numer faktury nie jest przekazywany.
- `[BRAK]` `positions` (`13-...md:22-30`): `name`, `code`, `quantity` (min. 0), `unit`, `grossAmount`, `taxStake`
  (wymagane) oraz `discountAmount` (opcjonalne). **Brak `taxAmount`** — inaczej niż w rozdz. 11.
  Implementacja musi rozróżniać oba kształty `invoice`, bo dzielą nazwę pola.
- `[BRAK]` `basisForVatExemption` (`13-...md:36`) — enum 19-wartościowy: `DENTAL_TECHNICAN_SERVICES`,
  `DOCTOR_DENTIST_SERVICES`, `PHYSIOTHERAPY_SERVICES`, `NURSING_SERVICES`, `PSYCHOLOGICAL_SERVICES`,
  `MEDICAL_TRANSPORT_SERVICES`, `CARE_SERVICES`, `TUTORING`, `TEACHING_FOREIGN_LANGUAGES`, `ARTISTS`,
  `RENTING_PROPERTY`, `INSURANCE_SERVICES`, `CREDITS_AND_LOANS_SERVICES`, `GUARANTIEES`,
  `SPECIAL_CONDITIONS_FOR_EXEMPTION`, `UE_TRANSACTIONS`, `SUBJECTIVE_EXEMPTIONS`, `OTHER`,
  `OTHER_OBJECTIVE_EXEMPTIONS`; plus `text` obowiązkowy przy `type = OTHER`.
  (Literówka `GUARANTIEES` jest w specyfikacji — implementacja musi ją powielić, inaczej wartość zostanie odrzucona.)
- `[BRAK]` `isKsef` (`01-utworzenie-platnosci.md:32`) — flaga `1`/`0` generowania identyfikatora płatności KSeF.
  Pole główne `/payment`, nie część `invoice`. Nie ma go nigdzie w `src/`.
- `[INFO]` Jak w rozdz. 11 — `invoice` po zakodowaniu jest stringiem, więc przechodzi przez `Fluent`
  i `buildQuery()` bez problemu. Brakuje kodera, DTO i walidacji, nie mechanizmu.
- `[INFO]` `docs/paywall.md:81-83` deklaruje „ING Księgowość (bookkeeping solution) — Not implemented.” Zgodne.

---

## 14 — ING Lease Now

**Werdykt: NIEZAIMPLEMENTOWANE (poza wartością `visibleMethod`).**

| Element spec | Repo | Status |
|---|---|---|
| `visibleMethod` = `lease` (`14-...md:110`) | `src/Types/PaymentMethod.php:13` | OK |
| Pole `cart` w `/payment` | **brak** | `[BRAK]` |
| `cart.items[]` — 11 pól (`14-...md:6-19`) | **brak** | `[BRAK]` |
| Koder `base64_encode(gzencode(json_encode($cart), 5))` (`14-...md:56`) | **brak** | `[BRAK]` |
| Progi: min. 1000 PLN / produkt, min. 5000 PLN / koszyk, netto (`14-...md:21`) | **brak walidacji** | `[BRAK]` |

### Ustalenia

- `[BRAK]` Pola `cart.items[]` do dodania (`14-...md:6-19`): `id` (TAK), `name` (TAK), `amount` (TAK, integer,
  grosze **netto**, dopuszczalne `0`), `tax` (TAK, integer), `taxStake` (TAK), `quantity` (TAK, integer, min. 1),
  `url` (TAK), `categoryId` (TAK), `unit` (NIE), `state` (NIE, `NEW`|`USED`), `discount.amount` (NIE), `discount.tax` (NIE).
- `[BRAK]` `taxStake` dla Lease Now ma **inny zestaw wartości** niż w rozdz. 11/13: `23`, `22`, `8`, `7`, `5`, `3`, `0`
  (numerycznie, **bez** prefiksu `TAX_`) oraz `TAX_EXEMPT`, `TAX_NOT_LIABLE`, `TAX_REVERSE_CHARGE`, `TAX_EXCLUDING`.
  Jeden wspólny enum dla obu modułów nie wystarczy.
- `[INFO]` `14-...md:17` — `state` jest w tabeli opisany jako typ `integer`, ale dopuszczalne wartości to
  `NEW` / `USED` (stringi). Niespójność w samej specyfikacji; przy implementacji iść za wartościami.
- `[INFO]` Tak jak `invoice`, zakodowany `cart` jest stringiem i przejdzie przez `Fluent` + `buildQuery()`.
  Brakuje DTO, kodera i walidacji progów.
- `[INFO]` `docs/paywall.md:85-87` deklaruje „ING Lease Now — Not implemented.” Zgodne.

---

## 15 — Sandbox

**Werdykt: ZGODNE.**

| Element spec | Repo | Status |
|---|---|---|
| Tryb sandbox jako osobne środowisko | `src/Types/Environment.php:10` `SANDBOX` | OK |
| `https://paywall.sandbox.pay.ing.pl/payment` | `src/Types/Environment.php:31` | OK |
| `https://paywall.sandbox.pay.ing.pl/{lang}/payment` | `src/Types/Environment.php:25` | OK |
| Widżet sandbox `https://paywall.sandbox.pay.ing.pl/js/widget.min.js` | `src/Types/Environment.php:39` | OK |
| Przełączanie środowiska konfiguracją | `IMOJE_ENV` → `ImojeServiceProvider.php:29` | OK (z zastrzeżeniem z rozdz. 00) |

### Ustalenia

- `[INFO]` Wszystkie trzy adresy sandboxowe dotykane przez paywall zgadzają się ze spec co do znaku.
  `tests/TestCase.php:41` domyślnie ustawia `Environment::SANDBOX`, więc testy nigdy nie celują w produkcję.
- `[INFO]` `15-sandbox.md:24-30` — testowe transakcje PBL/BLIK wyzwala się wartością `orderDescription`
  (`TEST-100000`, `TEST-100010`). To zwykły string, więc działa przez istniejące `orderDescription`
  (`src/DTO/Paywall/TransactionDto.php:62`) bez żadnych zmian.
- `[BRAK]` Brak fixture'ów/stałych dla 8 kart testowych (`15-sandbox.md:13-22`) i dla kodów `TEST-*`.
  Czysto wygodowe, nie blokuje integracji.
- `[INFO]` `src/Types/Environment.php:43-46` — `cdnUrl()` zwraca `https://data.imoje.pl` niezależnie od środowiska
  i jest używany tylko do logotypów (`src/Types/PaymentMethodCode.php:75`). Specyfikacja paywalla nie dokumentuje
  żadnego hosta CDN, więc nie mogę tego zweryfikować wobec spec — odnotowuję jedynie, że po migracji hostów
  (commit `b6821f4`, `pay.ing.pl`) jest to jedyne miejsce w `src/` wciąż wskazujące na domenę `imoje.pl`,
  a jedyny host CDN występujący w specyfikacjach to `https://cdn.pay.ing.pl` (plik Postmana). Wymaga potwierdzenia u dostawcy.

---

## `docs/paywall.md` — dokumentacja konsumencka

**Werdykt: ROZBIEŻNOŚCI — dokumentacja jest przestarzała, niekompletna i zawiera niedziałający przykład.**

| Aspekt | Stan | Status |
|---|---|---|
| Linki do dokumentacji dostawcy | `docs/paywall.md:5-6` → `imojepaywall.docs.apiary.io` / `imojepaywalleng.docs.apiary.io` | `[ROZBIEŻNOŚĆ]` |
| Przykład `TransactionDto` | `:24-33`, poprawny | OK |
| Parametr `$lang` | nieudokumentowany | `[BRAK]` |
| Mechanizm GET/redirect | nieopisany | `[BRAK]` |
| Przykład widżetu | `:49-75`, niekompletny + błąd składni | `[BŁĄD]` |
| Moduły „Not implemented” | `:77-87` — multipayout, księgowość, leasing | `[INFO]` uczciwe |

### Ustalenia

- `[ROZBIEŻNOŚĆ]` `docs/paywall.md:3-6` — kieruje konsumenta do **starej dokumentacji imoje na Apiary**,
  podczas gdy źródłem prawdy jest `https://bump.sh/pgw/doc/ing-pay-paywall` (ING Pay). Repo zmigrowało już
  hosty na `pay.ing.pl` (commit `b6821f4`), ale dokumentacja została przy poprzednim dostawcy i marce.
- `[BŁĄD]` `docs/paywall.md:61-62` — przykład nie jest poprawnym PHP: `'amount' => 100 * 100 // 100zł`
  nie ma przecinka na końcu, więc następna linia `'currency' => Currency::PLN,` wywoła *parse error*.
  Konsument kopiujący snippet dostanie błąd składni.
- `[BŁĄD]` `docs/paywall.md:46-75` — sekcja „OneClick payment” twierdzi „The package helps you quickly apply
  the widget form on your page”, po czym przekazuje `$dto` do widoku i **urywa się**. Brakuje tego, co spec
  (`08-...md:63-78`) uznaje za obowiązkowe: znacznika `<script>` z `id="imoje-widget__script"`, mapowania
  `camelCase → data-kebab-case` i informacji, że błędne mapowanie łamie podpis. Dokumentacja obiecuje więcej, niż pakiet daje.
- `[BRAK]` `docs/paywall.md:22-33` — nie opisuje drugiego parametru `createTransaction(TransactionDto $dto, ?Lang $lang = null)`
  (`src/Lib/Paywall.php:18-21`), więc wymuszenie języka bramki (rozdz. 03) jest dla konsumenta niewidoczne.
- `[BRAK]` `docs/paywall.md` — brak informacji, że pakiet przekierowuje **GET-em** z parametrami w query stringu,
  i brak ostrzeżenia o konsekwencjach (długość URL, PII w logach i `Referer`). Konsument nie ma jak podjąć
  świadomej decyzji o ewentualnym własnym POST-cie.
- `[BRAK]` `docs/paywall.md` — moduły **Raty** (rozdz. 10), **Split Payment** (rozdz. 11) i **płatności odroczone**
  (rozdz. 07) nie są wymienione w ogóle, nawet jako „Not implemented”, w przeciwieństwie do trzech pozostałych (`:77-87`).
  Dla odroczonych jest to szczególnie mylące, bo one **działają** — wystarczy `visibleMethod = imoje_paylater`.
- `[BRAK]` `docs/paywall.md` — brak opisu pól `visibleMethod`, `preselectMethodCode`, `validTo`, `urlNotification`,
  `billing`/`shipping`, `isKsef`. Konsument nie dowie się ani co jest wspierane, ani czego brakuje.
- `[INFO]` `docs/paywall.md:11` — „It'll add `serviceId`, `merchantId` and `signature` by default” jest zgodne
  z kodem (`src/DTO/Paywall/TransactionDto.php:73-78`).

---

## Testy

**Werdykt: ROZBIEŻNOŚCI — pokrycie paywalla jest symboliczne; żaden z błędów wykrytych w tym audycie nie byłby przez nie złapany.**

| Plik | Co pokrywa | Luka |
|---|---|---|
| `tests/Lib/PaywallTest.php:13-15` | jedna asercja: wynik jest `RedirectResponse` | brak sprawdzenia hosta, ścieżki, query, kodowania i podpisu |
| `tests/Lib/UtilsTest.php:13-18` | podpis dla 2 płaskich stringów | brak przypadku z tablicą zagnieżdżoną |
| `tests/Lib/UtilsTest.php:22-27` | `buildQuery` dla 2 płaskich stringów | j.w. |
| `tests/Lib/UtilsTest.php:29-69` | `transformValues`: skalar, `Arrayable`, enum — wszystko na 1 poziomie | brak zagnieżdżeń |
| `tests/Lib/UrlTest.php:11-79` | 17 adresów **RESTful API** | **zero** adresów paywalla/widżetu |

### Ustalenia

- `[BRAK]` `tests/Lib/PaywallTest.php:13-15` — test sprawdza wyłącznie typ zwracanej wartości.
  Nie weryfikuje, że URL to `https://paywall.sandbox.pay.ing.pl/payment`, że query zawiera komplet pól,
  że `signature` ma format `hash;alg`, ani że wartości są poprawnie zakodowane. Praktycznie każda regresja
  w `Paywall::createTransaction()` przejdzie.
- `[BRAK]` Brak testu wariantu z językiem (`createTransaction($dto, Lang::EN)` → `/en/payment`) —
  przez co błąd `ua`/`uk` (rozdz. 03) pozostaje niezauważony.
- `[BRAK]` `tests/Lib/UtilsTest.php` — brak testu `buildQuery()` z zagnieżdżoną tablicą, mimo że spec
  dostarcza gotowe wektory: `02-autoryzacja.md:57-72` i `12-multiwyplaty.md:37`.
  Dodanie jednego takiego testu natychmiast ujawniłoby `[BŁĄD]` z rozdz. 02.
- `[BRAK]` Brak jakiegokolwiek testu `OneClickDto` i `OneClickDtoFactory` — klasy nie są dotknięte przez suite.
- `[BRAK]` Brak testu regresji dla `array_merge_recursive` (przekazanie własnego `serviceId`) i dla
  „ducha” `amount => 0` przy pominiętym `amount`.
- `[INFO]` `tests/PlaygroundTest.php:20-33` zawiera pustą sekcję `try {}` i jest domyślnie pominięty
  (`:18`, `PLAYGROUND=0`). Nie wnosi pokrycia.

---

## Rekomendowana kolejność napraw

1. **`Utils::buildQuery()` + `transformValues()`** — rekurencja wg `02-autoryzacja.md:33-51`
   (`ksort` na każdym poziomie, klucz dziecka jako `rodzic[dziecko]`, rekurencyjna konwersja enum/`Arrayable`).
   Odblokowuje `billing`, `shipping`, `multipayout` i zdejmuje ryzyko cichego fałszowania podpisu.
   Test regresyjny: wektor z `12-multiwyplaty.md:37`.
2. **`array_merge_recursive` → `array_merge`** w `src/DTO/Paywall/TransactionDto.php:73`
   i `src/DTO/Paywall/OneClickDto.php:78`.
3. **`Lang::UA` → `Lang::UK = 'uk'`** (`src/Types/Lang.php:18`) + dodanie `bg`, `sv` dla widżetu/API.
   Zmiana łamiąca API pakietu → wymaga bumpa MAJOR wg `CONTRIBUTING.md`.
4. **Asymetria `null`** — ujednolicić `buildQuery()` i `Arr::query()` (albo filtrować `null` przed podpisem,
   albo wysyłać puste wartości), tak by `validTo = NULL` ze spec dało się zrealizować.
5. **Uzupełnić `TransactionDto`** o `urlNotification`, `preselectMethodCode`, `additionalDescription`,
   `isKsef`, `billing`, `shipping` (`BillingDto` już istnieje w `src/DTO/Casts/`).
6. **Warstwa widżetu** — renderer `data-*` z listą wykluczeń dla parametrów konfiguracyjnych (`08-...md:52,87`).
7. **Moduły** — `invoice` (11/13), `cart` (14), `multipayout` (12), kalkulator rat (10) wraz z koderem
   `base64_encode(gzencode(json_encode($x), 5))`.
8. **Dokumentacja i testy** — przepiąć `docs/paywall.md` na bump.sh, naprawić przykłady, rozszerzyć
   `PaywallTest` o asercje na URL i query.
