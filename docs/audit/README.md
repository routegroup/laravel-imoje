# Audyt zgodności ze specyfikacją ING Pay

Data: 2026-10-25 · Gałąź: `agent/ing-pay-api-audit-5dd10a` · Stan repo: `b8fb4bf`

Źródła prawdy (pobrane jako OpenAPI 3.1):

- `https://bump.sh/pgw/doc/ing-pay-api` — **ING Pay RESTful API v1.0.2**, 19 endpointów, 16 rozdziałów instrukcji
- `https://bump.sh/pgw/doc/ing-pay-paywall` — **ING Pay FRONT API v1.0.0**, 0 endpointów (brak `paths`), 15 rozdziałów instrukcji

## Pokrycie

| | przeszło audyt |
|---|---|
| Endpointy RESTful API | **19 / 19** |
| Rozdziały instrukcji API | **16 / 16** |
| Rozdziały instrukcji Paywall | **15 / 15** |
| Schematy `components.schemas` | 58 |

## Werdykt

**Żaden endpoint nie jest nieobecny.** Wszystkie 19 metod istnieje w `Lib\Api`, wszystkie URL-e,
metody HTTP i parametry ścieżki zgadzają się ze specyfikacją. Gdyby audyt kończył się na poziomie
„czy endpoint jest zaimplementowany", wynik brzmiałby 19/19.

Rozbieżności leżą o poziom niżej — w `BaseDto`, w obsłudze kodów HTTP i w algorytmie podpisu.
Są to w większości **defekty systemowe**: jeden wzorzec powielony w kilkunastu plikach, nie
kilkanaście niezależnych usterek.

| etykieta | liczba | znaczenie |
|---|---:|---|
| `[BŁĄD]` | 68 | zaimplementowane niezgodnie, realne ryzyko w działaniu |
| `[BRAK]` | 76 | jest w specyfikacji, nie ma w repo |
| `[ROZBIEŻNOŚĆ]` | 96 | różnica bez bezpośredniego ryzyka |
| `[NADMIAR]` | 30 | jest w repo, nie ma w specyfikacji |
| `[INFO]` | 97 | obserwacja, w tym błędy w samej specyfikacji ING |
| **razem** | **367** | |

Z 76 pozycji `[BRAK]` **39 pochodzi z jednego obszaru** — nieobsłużonych modułów paywalla
(raty, split payment, multiwypłaty, ING Księgowość, Lease Now).

## Defekty systemowe

Osiem wzorców odpowiada za większość ustaleń `[BŁĄD]`. Kolejność według ryzyka.

### S1 — Ciche połykanie błędów HTTP

`src/Lib/Api.php:236-249`. `validateResponse()` reaguje na **2 z 9** kodów ze specyfikacji
(tylko 422 i 500); `default => null` przepuszcza 400, 401, 403, 404, 406, 503 i 504 bez wyjątku.
Ciało błędu trafia do konstruktora DTO odpowiedzi i wraca do wywołującego jako sukces.

Potwierdzone skutki w konkretnych endpointach:

| wywołanie | kod ze spec | co dostaje wywołujący |
|---|---|---|
| `cancelPayment()` | 400 „Cannot cancel payment" | `CancelPaymentResponseDto` z `id = ''`, `amount = 0`, `status = null` |
| `chargeProfile()` | 400 „Payment profile inactive" | transakcja z samymi `null` |
| `createTransaction()` | 406 (nieaktywny kanał) | pusty `transaction`, `ActionDto` z `url = ''` |
| `createRefund()` | 403 / 404 / 503 | **nieudany zwrot wygląda jak udany** |
| `canRefund()` | 403 / 503 | `refundable: false` — nieodróżnialne od decyzji ING |
| `getTrustedIps()` | 401 / 403 | `trustedIps = null` → wzorzec „odczytaj-dodaj-zapisz" **kasuje listę kontroli dostępu** |
| `getServices()` | 403 / 404 / 503 | pusta kolekcja — „merchant nie ma sklepów" |

Zły token (401) również przechodzi bez sygnału. W `tests/` nie ma ani jednego testu ścieżki błędu.

### S2 — `array_merge_recursive` zamiast `array_merge`

Siedem DTO wstrzykuje domyślny `serviceId` z konfiguracji przez `array_merge_recursive`:
`Api/TransactionDto.php:97`, `Api/PaymentDto.php:71`, `Api/CancelPaymentDto.php:39`,
`Api/RefundDto.php:48`, `Api/ChargeProfileDto.php:58`, `Paywall/TransactionDto.php:73`,
`Paywall/OneClickDto.php:78`.

Dla kluczy tekstowych `array_merge_recursive` **scala**, zamiast nadpisać. Zweryfikowane na PHP 8.3:

```
array_merge_recursive(['serviceId' => 'cfg'], ['serviceId' => 'X'])
→ ['serviceId' => ['cfg', 'X']]
→ po castcie 'string': "Array"  (+ PHP Warning)
```

Skutek: integrator z więcej niż jednym sklepem **nie może** wskazać sklepu — dostaje 422.
W `RefundDto` dotyczy to także pola `type` (enum) → `TypeError` z konstruktora.
Żaden test tego nie łapie, bo fabryki nie ustawiają `serviceId`.

### S3 — `BaseDto` kasuje wszystkie wartości falsy

`src/DTO/BaseDto.php:30-32` (`array_filter` bez callbacku) i `:39-41` (`empty()`).
Zweryfikowane na PHP 8.3 — z ośmiu pól testowych przeżyło jedno:

```
['amount'=>0, 'isActive'=>false, 'title'=>'', 'orderId'=>'0',
 'nullable'=>null, 'ok'=>'keep', 'zeroF'=>0.0, 'emptyArr'=>[]]
→ ['ok' => 'keep']
```

Ginie więc `amount = 0`, `isActive = false`, `title = ''`, `paidAmount = 0`, `fee.merchant = 0`
oraz `orderId = '0'`. Specyfikacja wprost używa pustego `title` w przykładzie weryfikacji podpisu,
a `statusCodeDescription = ''` jest wg rozdz. 13 zwracane systemowo.

Konsekwencja osobna: `validTo = null` oznacza w specyfikacji „link bez wygaśnięcia" i **nie da się
tego wysłać** — pole jest wycinane przed serializacją.

Przy `allowNull = false` działa odwrotna patologia: `(int) null → 0`, `(bool) null → false`,
więc brakujące pole staje się zerem (dotyczy m.in. `PaymentProfileDto`, `CanRefundResponseDto`).

### S4 — Enumy rzutowane przez `from()`, nie `tryFrom()`

`src/DTO/BaseDto.php:58`. Każda wartość spoza enuma kończy się niewyłapanym `\ValueError`
**przy parsowaniu odpowiedzi**, nie tylko przy budowie żądania. Specyfikacja wymaga tolerancji
na nowe wartości (`components.schemas.additionalProperties`). Dziś wszystkie enumy są nadzbiorami
specyfikacji — poza jednym wyjątkiem opisanym w S7 — więc problem jest uśpiony do pierwszego
nowego kanału płatności po stronie ING.

### S5 — Podpis paywalla jest cicho fałszywy dla danych zagnieżdżonych

`src/Lib/Utils.php:42-45`. Referencyjny `prepareData()` ze specyfikacji (rozdz. 02) jest
rekurencyjny: robi `ksort` na każdym poziomie i buduje klucze `rodzic[dziecko]`.
`buildQuery()` w repo ma jedną pętlę bez gałęzi `is_array()`. Zweryfikowane na PHP 8.3:

```
SPEC : amount=300&multipayout[0][amount]=100&multipayout[0][ban]=72105000028166973380325415&…
REPO : amount=300&multipayout=Array&orderId=123        ← tylko PHP Warning
```

PHP nie przerywa wykonania — emituje warning (w produkcji zwykle wyciszony) i wstawia literał
`Array`. Podpis jest policzony, wygląda poprawnie i **jest nieprawidłowy**.

Blokuje to `billing`, `shipping` i `multipayout`. Multiwypłat nie da się obejść po stronie
konsumenta biblioteki. `transformValues()` (`:49-68`) pogarsza sprawę: zamienia `Arrayable` na
tablicę, czyli aktywnie produkuje wartość, której `buildQuery()` nie umie obsłużyć.

Powiązana asymetria (zweryfikowana):

```
buildQuery(['amount'=>'300','validTo'=>null,'orderId'=>'123'])  → amount=300&orderId=123&validTo=
Arr::query(…)  == http_build_query(…, PHP_QUERY_RFC3986)        → amount=300&orderId=123
```

Każde pole przekazane jako `null` wchodzi do podpisu, ale nie do żądania → rozjazd podpisu.

### S6 — Weryfikacja podpisu notyfikacji

`src/Lib/Utils.php:32` używa `===` zamiast `hash_equals()`. To jedyny mechanizm uwierzytelniania
notyfikacji; specyfikacja nazywa go krytycznym i zastrzega, że status transakcji wolno zmienić
dopiero po poprawnej weryfikacji. Poprawka to jedna linia.

Dalej w tej samej ścieżce:

- `src/Lib/Validator.php:129` — `HashMethod::from($header['alg'] ?? '')` rzuca `\ValueError`
  (nie `InvalidSignatureException`) przy braku lub nieznanym `alg`; PHPDoc deklaruje tylko ten drugi.
  Wg specyfikacji nie-200 uruchamia cykl 23 ponowień przez ~5 dni.
- `merchantid` i `serviceid` są z nagłówka parsowane, ale **nigdy nieweryfikowane**
  (`Validator.php:124-144`), mimo że `Config` ma oba pod ręką.
- Sprawdzenie pustego body następuje **po** `HashMethod::from`.
- Schemat `fromNotification()` (`Validator.php:30-86`) nie ma top-levelowego `required` ani
  `additionalProperties: false` — `{"cokolwiek": 1}` przechodzi walidację.
- `Utils::mockHeaders()` (`:74`) używa `JSON_UNESCAPED_SLASHES` bez `JSON_UNESCAPED_UNICODE`,
  podczas gdy test używa obu → rozjazd na polskich znakach, które specyfikacja dopuszcza.

### S7 — `Lang::UA = 'ua'` zamiast `uk`

`src/Types/Lang.php:18`. Zweryfikowane wobec `schemas.locale.enum`:

| | wartości |
|---|---|
| spec (17) | pl en cs de es fr it lt ru sk sl **uk** nl hu ro **bg** **sv** |
| repo (15) | pl en cs de es fr it lt ru sk sl **ua** nl hu ro |

Ciąg `"ua"` nie występuje w żadnej z dwóch specyfikacji. Dwa skutki: `Environment::paywallUrl()`
buduje nieistniejący `https://paywall.pay.ing.pl/ua/payment`, a `CustomerDto` castuje `locale`
tym samym enumem **przy odczycie**, więc odpowiedź API z `locale: "uk"` wywraca parsing
(`\ValueError`, patrz S4). Ukraińskiego nie da się ustawić w ogóle.

Naprawa zmienia publiczne API pakietu → bump MAJOR wg `docs/adr/0001-package-semver.md`.

### S8 — Host CDN nie został zmigrowany

`src/Types/Environment.php:45` zwraca `https://data.imoje.pl`. Zweryfikowane: `cdn.pay.ing.pl`
występuje w specyfikacji API **9×**, `data.imoje.pl` — **0×** w obu dokumentach.
Dotyka `PaymentMethodCode::getLogo()` (`src/Types/PaymentMethodCode.php:67-76`) oraz czterech
fixture'ów. Commit `b6821f4` zmigrował hosty API i paywalla, ale CDN pominął.

## Rozbieżności strukturalne poza wzorcami

- **`multipayout` w złym miejscu payloadu.** `src/DTO/Api/TransactionDto.php:41,89` trzyma je
  płasko. Zweryfikowane w `components.schemas`:

  | schemat | położenie |
  |---|---|
  | `transaction` | **tylko** `data.multipayout` (`data.required: [multipayout]`) |
  | `debit-profile` | **tylko** `data.multipayout` |
  | `payment` | najwyższy poziom |
  | `refund`, `can-refund` | nie występuje |

  Klucz `data` nie istnieje w `Api\TransactionDto` w ogóle. Płaski `multipayout` jest poprawny
  wyłącznie dla linku płatności.

- **`getProfileByCid()` używa DTO dla pojedynczego profilu**, choć endpoint zwraca listę.
  `src/Lib/Api.php:150-157` + `ProfileResponseDto:22-24` vs `get-profile-by-cid-response-200`
  (`{paymentProfiles: [...]}`). Brak odpowiednika `ProfilesResponseDto`, mimo że dla sklepów
  repo ma parę `ServiceResponseDto` / `ServicesResponseDto`.

- **`GET` i `PUT /settings/ips` mają w specyfikacji dwa rozłączne kształty odpowiedzi**
  (`{trustedIps}` vs `{status}`), a repo zwraca dla obu jeden `TrustedIpsResponseDto` będący ich
  unią — po PUT `trustedIps` jest zawsze `null`, po GET `status` zawsze `null`.

- **`sendRefundConfirmationEmail` jest wysyłane w każdym żądaniu jako `false`**
  (`src/DTO/Api/RefundDto.php:29` — jedyne DTO zwrotowe bez `allowNull`). Repo nie daje możliwości
  pominięcia pola, czyli jawnie wyłącza e-mail potwierdzający dla płatnika.

- **`payerData`, `payerCardData`, `refunds`, `transactions` bez castów** — surowe tablice zamiast
  DTO, mimo że `payerData` („dane z banku płatnika") jest w opisie endpointu reklamowane jako jego
  kluczowa funkcja.

- **`ApiErrorResponseDto:20` deklaruje cast `'errors' => 'array'`, który nie istnieje** —
  `BaseDto::castAttribute()` nie ma gałęzi `'array'`, więc pole zostaje `null` wbrew docblockowi.
  `ApiErrorException` zapisuje na sztywno kod 422, także dla błędów 500.

## Moduły bez żadnego wsparcia

Istnieją w specyfikacji, w repo nie ma po nich śladu (`grep` po `src/`):

| moduł | czego brakuje |
|---|---|
| Google Pay / Apple Pay 0 level | `wallet.{signature,protocolVersion,signedMessage}`, `wallet.paymentData.*`, `additionalData.browser` |
| ING Księgowość | `isKsef` (0 trafień), `invoice` w `PaymentDto`, `basisForVatExemption` (19 wartości) |
| ING Lease Now | `CartDto` / `CartItemDto`, `cart.items` (11 pól), `taxStake` |
| Raty (paywall) | `installmentsUrl()`, DTO kalkulatora, pola `channel` / `period` |
| Split Payment (paywall) | cały rozdział 11 |
| Multiwypłaty (paywall) | zablokowane przez S5 |
| Notyfikacje raportowe | `id`/`status`/`fileExt`/`created`/`modified`/`url`, **bez sygnatury** — dziś gwarantowany 500 na tym samym endpoincie (S6) |
| Widżet kartowy | adresy i 18 z 24 parametrów są OK, brak warstwy renderującej (`data-kebab-case`, `id="imoje-widget__script"`, wykluczenia pól z podpisu) |

Wszystkie moduły fakturowe wymagają wspólnego kodera `base64_encode(gzencode(json_encode($x), 5))`
— poziom kompresji **5** jest w specyfikacji podany wprost.

## Nadmiary wobec specyfikacji

Enumy w repo są nadzbiorami specyfikacji (żadnej wartości ze spec nie brakuje — poza `Lang`, S7):

- `Currency`: `UAH`, `HRK` (waluta wycofana)
- `TransactionStatus`: `submitted_for_settlement` (0 trafień w spec), `refund` (w spec to **typ**, nie status)
- `PaymentMethod`: `visa_mobile` (w żadnym enumie metod — wg rozdz. 03 to kanał metody `wallet`)
- `PaymentMethodCode`: 7 wartości, w tym mylące `imoje_installments` (w spec to **metoda**, nie kanał)
- `RefundNotificationDto::$originalTransactionId` — 0 trafień w całej specyfikacji

Pułapka typowa: `paymentMethod` i `visibleMethod` to w specyfikacji **dwa różne enumy**
(`ing` jest dozwolone w pierwszym, nie w drugim; `lease` odwrotnie), a repo używa na oba
jednego `PaymentMethod`.

Martwy kod: `NotImplementedException`, `CantChangeTransactionStatus`, `InvalidDtoNameException`
nie są rzucane nigdzie; `TransactionStatus::canChange()` nie jest wywoływany, a jego reguła
`settled → refund` jest sprzeczna z modelem ING Pay. Specyfikacja nie definiuje maszyny stanów.

## Testy

Żaden z defektów S1–S8 nie zostałby złapany przez obecny suite.

- Zero testów ścieżek błędu — `ApiTest.php` sprawdza wyłącznie klasę zwróconego obiektu.
- `PaywallTest.php:13-15` ma jedną asercję (`toBeInstanceOf`): nie sprawdza hosta, ścieżki,
  query ani podpisu. `UrlTest.php` pokrywa 17 adresów API i **zero** paywallowych.
- Test „expects to throw InvalidSignatureException" (`ValidatorTest.php:115-124`) trafia w gałąź
  pustego body, nie w niezgodny podpis. **Nie ma testu na faktycznie błędną sygnaturę**, a
  `UtilsTest.php:20` skipuje `verifySignature` z nieprawdziwą adnotacją „Tested in ValidatorTest".
- Fixture'y utrwalają błędy zamiast je wykrywać: `ServicesResponseDtoFactory:16` podstawia obiekt
  zamiast listy wymaganej przez spec; `ApiTest.php:170-174` testuje PUT payloadem GET-a;
  `ActionDtoFactory:29` ustawia `iban` zamiast `ban`; `CardDtoFactory` generuje 4-cyfrowy `year`
  przy `maxLength: 2`.

## Dokumentacja

- `docs/api.md` ma 6 linii i odsyła do **nieaktualnego** `imojeapi.docs.apiary.io` zamiast
  `bump.sh/pgw/doc/ing-pay-api`. To samo w `docs/paywall.md:5-6`.
- `docs/paywall.md:61-62` ma błąd składni PHP (brak przecinka po `'amount' => 100 * 100`),
  nie opisuje parametru `$lang` ani działających modułów (płatności odroczone).
- `docs/notifications.md:3` reklamuje walidację „schema structure", która nie waliduje nic (S6).
- `ImojeServiceProvider.php:29` — `Environment::from($config->get('services.imoje.env', <default>))`:
  default z `Arr::get` działa tylko przy braku klucza, a `README.md:41` każe zostawić `IMOJE_ENV=`
  puste → `\ValueError` na bootstrapie. Poprawka: `?:` zamiast trzeciego argumentu.

## Błędy w samej specyfikacji ING

Do zgłoszenia na tech@pay.ing.pl, nie do naprawy w repo:

- `get-payment-response-200` i `cancel-payment-response-200`: pole `currency` ma `$ref` na schemat
  `status` (przykład pokazuje `"PLN"`); `amount` ma typ `string` przy przykładzie `100`.
- `DELETE /profile/id/{id}` i `POST /profile/deactivate` nie mają klucza `responses`;
  odpowiadające komponenty są osierocone.
- `POST .../capture` i `POST .../void` nie mają ani `responses`, ani `requestBody` — mapowania
  odpowiedzi nie da się zweryfikować.
- Okna czasowe zwrotu są sprzeczne: opis operacji `refund` mówi 15 miesięcy (BLIK/Twisto/PayPal 12),
  a `06-zwroty.md` — BLIK 12, odroczone 12, **karty 3 lata**.
- `card` jest w tabeli rozdz. 03, ale nie ma go w enumie `paymentMethodCode`.
- `status.enum` zawiera `error`, którego nie ma w tabeli notyfikacji.
- `10-raty.md:50-52` używa w przykładzie wycofanego hosta `paywall.imoje.pl`.
- `13-ing-ksiegowosc.md` ma literówkę `GUARANTIEES` w `basisForVatExemption`.
- `14-ing-lease-now.md:17` opisuje `state` jako `integer`, a wartości to `NEW` / `USED`.

## Rekomendowana kolejność napraw

Kolejność według stosunku ryzyka do kosztu. Pozycje 1–3 to zmiany jedno- lub kilkuliniowe.

1. **`hash_equals()`** w `Utils::verifySignature` (S6) — jedna linia, luka bezpieczeństwa.
2. **`array_merge` zamiast `array_merge_recursive`** w 7 DTO (S2) — odblokowuje multi-sklep.
3. **`cdn.pay.ing.pl`** w `Environment::cdnUrl()` (S8).
4. **Rekurencyjny `buildQuery()`** wg referencji ze spec (S5) — odblokowuje `billing`,
   `shipping` i multiwypłaty; wymaga testu na wektorze ze specyfikacji.
5. **Pełna obsługa kodów HTTP** w `validateResponse` (S1) — `NotImplementedException` (501,
   „Given response has been not recognized") pasuje tu jako element rozwiązania.
6. **`array_filter(…, fn($v) => $v !== null)`** w `BaseDto` (S3) — zmiana zachowania, wymaga
   przejrzenia wszystkich DTO z `allowNull`.
7. **`tryFrom()` + fallback** w `BaseDto::castAttribute` (S4).
8. **`Lang`: `uk` zamiast `ua` + `bg`, `sv`** (S7) — **bump MAJOR**.
9. `multipayout` pod `data` dla `/transaction` i `/transaction/profile`.
10. `ProfilesResponseDto` dla `GET /profile/cid/{cid}`.

## Raporty szczegółowe

Tabele pole-po-polu, cytaty ze specyfikacji i odwołania `plik:linia`:

| | obszar | ustaleń |
|---|---|---:|
| [01](szczegoly/01-transakcje.md) | Transakcje (create, get, capture, void) | 52 |
| [02](szczegoly/02-profile.md) | Profile OneClick / recurring (5 endpointów) | 38 |
| [03](szczegoly/03-linki-platnosci.md) | Linki płatności (create, get, cancel) | 30 |
| [04](szczegoly/04-zwroty.md) | Zwroty (refund, can-refund) | 29 |
| [05](szczegoly/05-sklepy-metody-ip.md) | Sklepy, metody płatności, zaufane IP | 37 |
| [06](szczegoly/06-typy-bledy-przekrojowe.md) | Typy, enumy, kody HTTP, środowiska | 36 |
| [07](szczegoly/07-notyfikacje.md) | Notyfikacje (webhooki) | 51 |
| [08](szczegoly/08-paywall.md) | Paywall — 14 rozdziałów FRONT API | 94 |

## Metoda

Audyt prowadzony przez 8 równoległych agentów, po jednym na obszar, każdy wobec rozbitej na
pliki specyfikacji (per endpoint i per rozdział). Tezy dotyczące zachowania PHP zostały
**zweryfikowane wykonaniem** na `php:8.3-cli` (na hoście nie było interpretera ani `vendor/`):
semantyka `array_merge_recursive` przy kolizji kluczy tekstowych, `array_filter` bez callbacku,
`empty()` w `castAttribute`, rzutowanie `null`, parsowanie nagłówka `x-imoje-signature`,
rozjazd `buildQuery()` wobec referencyjnego `prepareData()` oraz asymetria `null` między
podpisem a `http_build_query`.

Ograniczenie: testy pakietu nie zostały uruchomione (brak `vendor/` w worktree). Ustalenia
dotyczące zachowania przy realnych odpowiedziach ING nie były weryfikowane wobec sandboxa.
