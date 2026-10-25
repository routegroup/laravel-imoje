# Profile OneClick / recurring

Zakres: 5 endpointów grupy `profile` z ING Pay RESTful API v1.0.2.
Repo: `/Users/khajiit/Code/routegroup/laravel-imoje/.claude/worktrees/ing-pay-api-audit-5dd10a` (branch `agent/ing-pay-api-audit-5dd10a`, HEAD `b8fb4bf`).
Spec: `scratchpad/spec/ing-pay-api.json` + `api-components.json`.

**Uwaga metodologiczna:** w worktree nie ma katalogu `vendor/` ani interpretera `php` (`php -v` → `command not found`), więc żadna teza nie została potwierdzona uruchomieniem kodu. Wszystkie ustalenia wynikają z lektury źródeł i udokumentowanej semantyki PHP/Laravel (`Illuminate\Support\Fluent`, `array_merge_recursive`, `array_filter`). Miejsca, w których to ma znaczenie, są oznaczone.

---

## 1. `POST /{merchantId}/transaction/profile` — Obciążenie profilu

**Werdykt: ROZBIEŻNOŚCI** — URL, metoda i komplet pól request body są zgodne, ale nieobsłużony jest udokumentowany kod `400`, a nadpisanie `serviceId` psuje payload.

### Metoda i URL

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `Api::chargeProfile` → `$this->request()->post(...)` (`src/Lib/Api.php:116`) | ZGODNE |
| URL | server `https://api.pay.ing.pl/v1/merchant` + `/{merchantId}/transaction/profile` | `Url::createChargeProfileUrl()` (`src/Lib/Url.php:66-72`) = `Environment::apiUrl()` (`src/Types/Environment.php:15-16`, `https://api.pay.ing.pl/v1`) + `/merchant/{merchantId}/transaction/profile` | ZGODNE |
| sandbox | `https://api.sandbox.pay.ing.pl/v1/merchant` | `src/Types/Environment.php:16` | ZGODNE |
| autoryzacja | HTTP Bearer (`Token autoryzacyjny`, `security` globalne w `ing-pay-api.json`) | `Api::request()` → `->withToken($this->config->apiKey)` (`src/Lib/Api.php:230`) | ZGODNE |

### Parametry ścieżki

| pole | spec (typ, required) | repo | status |
|---|---|---|---|
| `merchantId` | string, required (path) | `Config::$merchantId` wstrzykiwany w `Url` (`src/Lib/Url.php:70`) | ZGODNE |

### Request body — schemat `debit-profile`

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `serviceId` | string, **required**, maxLength 36, format uuid, pattern UUID v4 | `ChargeProfileDto` PHPDoc `string` (`src/DTO/Api/ChargeProfileDto.php:14`), domyślnie z `Config` (linie 58-60); brak castu, brak walidacji długości/wzorca | ROZBIEŻNOŚĆ (patrz [BŁĄD] P1-1) |
| `paymentProfileId` | string, **required**, maxLength 36, format uuid, pattern UUID v4 | PHPDoc `string` (linia 15); brak castu i walidacji | ROZBIEŻNOŚĆ |
| `amount` | integer, **required** | cast `'int'` (linia 33) | ZGODNE |
| `currency` | string, **required**, maxLength 3, enum: PLN, EUR, USD, GBP, CHF, SEK, HUF, CZK, BGN, RON (10) | cast `Currency::class` (linia 34); `src/Types/Currency.php:9-20` ma 12 wartości | NADMIAR (2 wartości ponad spec) |
| `orderId` | string, **required**, maxLength 100, pattern `^[A-Za-z0-9#_\-\.\\/ À-ˀ]+$` | PHPDoc `string` (linia 18); brak castu i walidacji | ROZBIEŻNOŚĆ |
| `title` | string, opcjonalne, maxLength 255, pattern | PHPDoc `string\|null` (linia 19) | ZGODNE |
| `notificationUrl` | string, opcjonalne, maxLength 300, format uri | PHPDoc `string\|null` (linia 20) | ZGODNE |
| `data` | object, opcjonalne; `required: [multipayout]`; `multipayout` = array of `{ban(26, pattern ^[0-9]{26}$), amount(int), label(70), title?(105)}`, required `ban`,`amount`,`label` | PHPDoc `array\|null` + `@todo` (linie 21, 49-50) | ROZBIEŻNOŚĆ |
| `invoice` | object, opcjonalne; required `buyer`+`positions`; enumy `type`, `idType`, `taxStake`(11), `basisForVatExemption.type`(19) | PHPDoc `array\|null` (linie 22, 51) | ROZBIEŻNOŚĆ |

Pól nadmiarowych względem specyfikacji w `ChargeProfileDto` **nie ma** — lista 9 właściwości odpowiada 1:1 `properties` schematu `debit-profile`.

### Response 200 — `debit-profile-response-200`

Kształt: `{transaction: {...}}`. `ChargeProfileResponseDto` castuje `transaction` → `DTO\Casts\TransactionDto` (`src/DTO/Responses/ChargeProfileResponseDto.php:20-22`). Kształt ZGODNY.

| pole `transaction.*` | spec (typ) | repo (`src/DTO/Casts/TransactionDto.php`) | status |
|---|---|---|---|
| `id` | string (transactionId) | cast `'string'` (linia 53) | ZGODNE |
| `type` | enum `sale`, `refund` | `TransactionType` (linia 54), `src/Types/TransactionType.php:9-10` — komplet | ZGODNE |
| `status` | enum `new`,`pending`,`settled`,`authorized`,`rejected`,`cancelled`,`error` | `TransactionStatus` (linia 55), `src/Types/TransactionStatus.php:9-17` — komplet + 2 nadmiarowe (`submitted_for_settlement`, `refund`) | NADMIAR |
| `source` | enum `web`, `api` | `TransactionSource` (linia 56) — komplet | ZGODNE |
| `created` | `[integer, null]` | cast `'int'`, `allowNull=true` (linie 50, 57) | ZGODNE |
| `modified` | `[integer, null]` | cast `'int'` (linia 58) | ZGODNE |
| `notificationUrl` | string | PHPDoc `string\|null` (linia 24), bez castu | ZGODNE |
| `serviceId` | string | cast `'string'` (linia 59) | ZGODNE |
| `amount` | integer | cast `'int'` (linia 60) | ZGODNE |
| `currency` | enum 10 wartości | `Currency` (linia 61) — superset | NADMIAR |
| `title` | string | PHPDoc `string` (linia 28), bez castu | ZGODNE |
| `orderId` | string | cast `'string'` (linia 62) | ZGODNE |
| `paymentMethod` | enum 8 wartości | `PaymentMethod` (linia 63), `src/Types/PaymentMethod.php:9-18` — komplet + `lease`, `visa_mobile` | NADMIAR |
| `paymentMethodCode` | enum 48 wartości | `PaymentMethodCode` (linia 64), `src/Types/PaymentMethodCode.php` — komplet + 7 nadmiarowych | NADMIAR |
| `payment.id` | string (paymentId) | `PaymentSummaryDto` cast `'string'` (`src/DTO/Casts/PaymentSummaryDto.php:17`) | ZGODNE |
| `payment.status` | enum status | `TransactionStatus` (`src/DTO/Casts/PaymentSummaryDto.php:18`) | ZGODNE |
| `paymentProfile` | obiekt inline (bez `email` i `firstTransactionId`) | `PaymentProfileDto` (linia 66) — superset | patrz sekcja 3 (castowanie `month`/`year`/`isActive`) |

### Kody odpowiedzi

| kod | spec | repo (`Api::validateResponse`, `src/Lib/Api.php:236-249`) | status |
|---|---|---|---|
| 200 | `debit-profile-response-200` | `$response->ok()` → return | ZGODNE |
| **400** | `debit-profile-response-400` — *„Profil jest nieaktywny”*, `apiErrorResponse.code = TRX-ERROR-120301` | `match` obsługuje tylko 422 i 500, `default => null` (linia 247) | BŁĄD |
| 401/403/404/406/422/500/503/504 | tabela globalna `ing-pay-api-topics/05-kody-odpowiedzi-http.md` | tylko 422 i 500 | ROZBIEŻNOŚĆ |

### Ustalenia

- **[BŁĄD] P1-1 — przekazanie własnego `serviceId` zamienia go w tablicę.** `src/DTO/Api/ChargeProfileDto.php:58-60` używa `array_merge_recursive(['serviceId' => $config->serviceId], $attributes)`. Zgodnie z semantyką `array_merge_recursive` identyczne klucze stringowe o wartościach skalarnych są łączone w tablicę, więc `new ChargeProfileDto(['serviceId' => 'inny-sklep', ...])` wyprodukuje `serviceId: ["domyślny","inny-sklep"]` w JSON body. Spec (`api-components.json` → `schemas.serviceId`) wymaga `type: string`, `maxLength: 36`, `pattern` UUID v4 — API odpowie 422. Dotyczy wyłącznie integratorów obsługujących więcej niż jeden sklep. Ten sam wzorzec występuje w 6 innych DTO (`src/DTO/Api/PaymentDto.php:71`, `TransactionDto.php:97`, `RefundDto.php:48`, `CancelPaymentDto.php:39`, `src/DTO/Paywall/TransactionDto.php:73`, `OneClickDto.php:78`) — jeśli inna sekcja audytu zgłasza to globalnie, tu traktuj jako duplikat. Nie zweryfikowano wykonaniem (brak PHP w worktree).
- **[BŁĄD] P1-2 — kod 400 „Payment profile inactive” przechodzi bez wyjątku.** `src/Lib/Api.php:244-248`: `match` ma gałęzie tylko dla 422 i 500, a `default => null`. Dla `POST /transaction/profile` specyfikacja wprost deklaruje odpowiedź 400 (`ing-pay-api.json` → `paths./{merchantId}/transaction/profile.post.responses.400` → `#/components/responses/debit-profile-response-400`). W efekcie `chargeProfile()` zwraca `ChargeProfileResponseDto` zbudowane z body błędu: `transaction` → pusty `TransactionDto` (bo `ChargeProfileResponseDto` nie ustawia `allowNull`, więc `BaseDto::castAttribute` dla `null` tworzy `new TransactionDto([])`, `src/DTO/BaseDto.php:43-47`), a `apiErrorResponse` zostaje surową tablicą. Obciążenie nieaktywnego profilu wygląda więc dla wywołującego jak sukces z pustą transakcją.
- **[NADMIAR] P1-3 — `Currency` zawiera waluty spoza specyfikacji.** `src/Types/Currency.php:14-15` definiuje `UAH` i `HRK`; `api-components.json` → `schemas.currency.enum` ma 10 wartości bez tych dwóch. Komplet wartości ze spec jest pokryty, więc `Currency::from()` nigdy nie rzuci na odpowiedzi API; ryzyko dotyczy tylko żądania (API zwróci 422).
- **[ROZBIEŻNOŚĆ] P1-4 — `data` i `invoice` bez typowania i walidacji.** `src/DTO/Api/ChargeProfileDto.php:21-22` i `49-51` (`@todo: add proper DTO for data and invoice`). Spec definiuje dla nich wymagane pola i 4 enumy (`taxStake` 11 wartości, `basisForVatExemption.type` 19 wartości, `buyer.type`, `buyer.idType`). Błędy wyjdą dopiero jako 422 z API.
- **[ROZBIEŻNOŚĆ] P1-5 — `allowNull = true` usuwa z body wszystkie wartości falsy, nie tylko `null`.** `src/DTO/Api/ChargeProfileDto.php:30` + `src/DTO/BaseDto.php:30-32` (`array_filter($attributes)` bez callbacka). `orderId` o wartości `'0'` (dozwolonej przez pattern `^[A-Za-z0-9#_\-\.\\/ À-ˀ]+$`) albo `title = '0'` zostaną wycięte z żądania, mimo że `orderId` jest w spec `required`.
- **[ROZBIEŻNOŚĆ] P1-6 — fabryka nie ustawia pól wymaganych przez spec.** `src/Factories/Api/ChargeProfileDtoFactory.php:17-20` ustawia tylko `amount` i `currency`. Brak `paymentProfileId` i `orderId`, które spec oznacza jako `required`. Test `tests/Lib/ApiTest.php:92-98` korzysta z tej fabryki i przechodzi, bo weryfikuje wyłącznie klasę zwróconego obiektu.
- **[INFO] P1-7 — w pakiecie nie ma żadnej walidacji `maxLength`/`pattern`/`format` dla żądań.** `src/Lib/Validator.php` obsługuje wyłącznie schemat notyfikacji (`fromNotification`, linie 26-89) i weryfikację sygnatury. Wszystkie ograniczenia ze specyfikacji (`maxLength 36/100/255/300`, patterny, `format: uuid/uri`) są egzekwowane dopiero po stronie API.
- **[INFO] P1-8 — schemat `paymentProfile` w odpowiedzi 400/200 tego endpointu jest inline i węższy niż współdzielony.** `api-components.json` → `responses.debit-profile-response-200` definiuje `transaction.paymentProfile` bez pól `email` i `firstTransactionId`, podczas gdy `schemas.paymentProfile` je ma. Repo ma jedno `PaymentProfileDto` z nadzbiorem pól — zachowanie poprawne dla obu kształtów.

---

## 2. `GET /{merchantId}/profile/cid/{cid}` — Profile wg cid

**Werdykt: ROZBIEŻNOŚCI** — URL i metoda zgodne, ale repo używa DTO odpowiedzi przeznaczonego dla pojedynczego profilu, przez co lista profili nie jest w ogóle hydratowana.

### Metoda i URL

| element | spec | repo | status |
|---|---|---|---|
| metoda | `GET` | `Api::getProfileByCid` → `->get($url)` (`src/Lib/Api.php:153`) | ZGODNE |
| URL | `/{merchantId}/profile/cid/{cid}` | `Url::createProfileCidUrl()` (`src/Lib/Url.php:113-123`) → `{apiUrl}/merchant/{merchantId}/profile/cid/{cid}` | ZGODNE |

### Parametry ścieżki

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `merchantId` | string, required | z `Config` (`src/Lib/Url.php:118`) | ZGODNE |
| `cid` | string, **required**, maxLength 36, pattern `^[A-Za-z0-9\x2D]+$` (tylko A-Za-z0-9 i myślnik) | `string $cid` wstawiany wprost do URL (`src/Lib/Url.php:113,121`), bez walidacji i bez `urlencode()` | ROZBIEŻNOŚĆ |

### Response 200 — `get-profile-by-cid-response-200`

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `paymentProfiles` | **array** of `#/components/schemas/paymentProfile` | brak — `ProfileResponseDto` castuje wyłącznie `paymentProfile` (singular) (`src/DTO/Responses/ProfileResponseDto.php:22-24`) | BŁĄD |
| `paymentProfiles[].*` | 13 pól schematu `paymentProfile` | nie są hydratowane do `PaymentProfileDto` — pozostają surowymi tablicami | BŁĄD |
| `paymentProfile` | **nie występuje** w tej odpowiedzi | zadeklarowane w `ProfileResponseDto` PHPDoc (linia 12) i w `casts` (linia 23) | NADMIAR |

### Kody odpowiedzi

| kod | spec | repo | status |
|---|---|---|---|
| 200 | `get-profile-by-cid-response-200` | `ok()` | ZGODNE |
| brak innych w operacji | tabela globalna (401/403/404/500/503/504) | tylko 422 i 500 w `validateResponse` | ROZBIEŻNOŚĆ |

### Ustalenia

- **[BŁĄD] P2-1 — `getProfileByCid()` zwraca DTO, które nie odwzorowuje kształtu odpowiedzi.** `src/Lib/Api.php:150-157` zwraca `ProfileResponseDto`, a ta klasa (`src/DTO/Responses/ProfileResponseDto.php:20-24`) ma `allowNull = true` i `casts = ['paymentProfile' => PaymentProfileDto::class]`. Dla realnej odpowiedzi `{"paymentProfiles": [...]}` (spec: `api-components.json` → `responses.get-profile-by-cid-response-200.content.application/json.schema.properties.paymentProfiles`, `type: array`) ścieżka wykonania to: `BaseDto::castAttributes` ustawia `paymentProfile = castAttribute(PaymentProfileDto::class, null)`; `src/DTO/BaseDto.php:39-41` przy `allowNull` i `empty(null)` zwraca `null`; `array_filter` (linia 31) usuwa ten klucz. Efekt: `$response->paymentProfile === null`, a `$response->paymentProfiles` to surowa tablica tablic, nieudokumentowana w PHPDoc i bez żadnego castu — `month`, `year`, `isActive` pozostają w formacie API, przeciwnie niż dla endpointu `GET /profile/id/{id}`. Konsument dostaje dwa różne typy danych dla tego samego profilu w zależności od użytej metody.
- **[BRAK] P2-2 — nie istnieje DTO dla listy profili.** W `src/DTO/Responses/` nie ma odpowiednika `ProfilesResponseDto` (porównaj: dla usług repo ma osobne `ServiceResponseDto` i `ServicesResponseDto`, `src/DTO/Responses/ServiceResponseDto.php` / `ServicesResponseDto.php`). Spec rozróżnia `get-profile-by-cid-response-200` (lista) i `get-profile-by-profile-id-response-200` (obiekt).
- **[BŁĄD] P2-3 — test utrwala błędny kształt zamiast go wykryć.** `tests/Lib/ApiTest.php:120-126` podstawia `Http::response(ProfileResponseDto::factory()->make()->toArray())`, a `src/Factories/Responses/ProfileResponseDtoFactory.php:17-20` generuje `['paymentProfile' => PaymentProfileDto]`, czyli `{"paymentProfile": {...}}` — kształt endpointu `/profile/id/`, nie `/profile/cid/`. Asercja (`tests/Lib/ApiTest.php:34-40`) sprawdza wyłącznie `toBeInstanceOf`, więc test przejdzie także przy całkowicie pustej odpowiedzi. `tests/Lib/ApiSpecTest.php` nie zawiera żadnego przypadku dla profili.
- **[ROZBIEŻNOŚĆ] P2-4 — `cid` nie jest walidowany ani enkodowany.** `src/Lib/Url.php:113-123` skleja wartość wprost. Spec nakłada `maxLength: 36` i `pattern: ^[A-Za-z0-9\x2D]+$`. `cid` zawierający `/`, spację lub `?` zmieni strukturę URL-a.
- **[INFO] P2-5 — niespójność w samej dokumentacji ING co do dozwolonych znaków `cid`.** Widżet kartowy przyjmuje `data-customer-id` ze znakami `A-Za-z0-9_-` (`ing-pay-paywall-topics/08-widzet-dla-platnosci-kartami.md`, wiersz `data-customer-id`), czyli z podkreśleniem, podczas gdy pattern parametru `cid` w API (`^[A-Za-z0-9\x2D]+$`) podkreślenia nie dopuszcza. Profil zarejestrowany z `customerId` zawierającym `_` może być nie do odczytania przez ten endpoint. Repo nie waliduje żadnej z tych wartości (`src/DTO/Paywall/OneClickDto.php:22` — `customerId` bez castu i wzorca), więc nie da się rozstrzygnąć na podstawie kodu, która reguła obowiązuje w praktyce.

---

## 3. `GET /{merchantId}/profile/id/{paymentProfileId}` — Profil wg id

**Werdykt: ROZBIEŻNOŚCI** — kształt odpowiedzi zgodny, ale trzy pola są castowane na typy inne niż w specyfikacji.

### Metoda i URL

| element | spec | repo | status |
|---|---|---|---|
| metoda | `GET` | `Api::getProfile` → `->get($url)` (`src/Lib/Api.php:106`) | ZGODNE |
| URL | `/{merchantId}/profile/id/{paymentProfileId}` | `Url::createProfileIdUrl()` (`src/Lib/Url.php:74-84`) | ZGODNE |

### Parametry ścieżki

Uwaga: w `api-endpoints/get-merchantid-profile-id-paymentprofileid.json` brak klucza `parameters` — parametry są zdefiniowane na poziomie *path item*, nie operacji (`ing-pay-api.json` → `paths./{merchantId}/profile/id/{paymentProfileId}.parameters`). Obowiązują więc oba poniższe.

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `merchantId` | string, required | z `Config` (`src/Lib/Url.php:79`) | ZGODNE |
| `paymentProfileId` | string, required, maxLength 36, format uuid, pattern UUID v4 | `string $profileId` wstawiany wprost (`src/Lib/Url.php:74,82`), bez walidacji | ROZBIEŻNOŚĆ |

### Response 200 — `get-profile-by-profile-id-response-200` → `schemas.paymentProfile`

Kształt `{paymentProfile: {...}}` odwzorowany poprawnie przez `ProfileResponseDto` (`src/DTO/Responses/ProfileResponseDto.php:22-24`).

| pole | spec (typ, required, ograniczenia) | repo (`src/DTO/Casts/PaymentProfileDto.php`) | status |
|---|---|---|---|
| `id` | string, maxLength 36, format uuid, pattern UUID v4 | bez castu, default `null` (linia 33); PHPDoc `string\|null` | ZGODNE |
| `merchantMid` | string | bez castu, default `null` (linia 34); PHPDoc `string` bez `\|null` (linia 13) | ROZBIEŻNOŚĆ |
| `merchantCustomerId` | string | bez castu (linia 35) | ZGODNE |
| `firstName` | string | bez castu (linia 36) | ZGODNE |
| `lastName` | string | bez castu (linia 37) | ZGODNE |
| `email` | string (RFC 5322/6531) | bez castu (linia 38) | ZGODNE |
| `maskedNumber` | string | bez castu (linia 39) | ZGODNE |
| `month` | **string** (przykład `"12"`) | cast `'int'` (linia 49) | BŁĄD |
| `year` | **string** (przykład `"2029"`) | cast `'int'` (linia 50) | BŁĄD |
| `organization` | string | bez castu (linia 42) | ZGODNE |
| `isActive` | **integer**, enum `[0, 1]` | cast `'bool'` (linia 51) | ROZBIEŻNOŚĆ |
| `profile` | string, enum `["ONE_CLICK", "RECURRING"]` | `string`, bez typu wyliczeniowego (PHPDoc linia 23) | ROZBIEŻNOŚĆ |
| `firstTransactionId` | string | bez castu (linia 45) | ZGODNE |
| `additionalProperties` | dowolne dodatkowe pola dozwolone | `Fluent` zachowuje nieznane klucze | ZGODNE |

### Kody odpowiedzi

| kod | spec | repo | status |
|---|---|---|---|
| 200 | `get-profile-by-profile-id-response-200` | `ok()` | ZGODNE |
| brak innych w operacji | tabela globalna | tylko 422/500 | ROZBIEŻNOŚĆ |

### Ustalenia

- **[BŁĄD] P3-1 — `month` i `year` castowane na `int`, choć spec deklaruje `string`.** `src/DTO/Casts/PaymentProfileDto.php:49-50` vs `api-components.json` → `schemas.paymentProfile.properties.month`/`.year` (`type: "string"`, przykład `"12"` / `"2029"`). Potwierdza to też dokumentacja notyfikacji: `ing-pay-paywall-topics/05-notyfikacje.md` — `month | string(2)`, `year | string(4)`. Skutek praktyczny: `"01"` → `1`, więc odtworzenie daty ważności karty w formacie `MM/YY` wymaga ponownego paddingu po stronie konsumenta, a porównanie `$profile->month === '01'` zawsze zwróci `false`. Fabryka utrwala tę konwersję: `src/Factories/Casts/PaymentProfileDtoFactory.php:26-27` generuje `format('m')`/`format('Y')` (stringi), które DTO zamienia na `int`.
- **[BŁĄD] P3-2 — brak `allowNull` powoduje, że nieobecne pola stają się `0`/`false` zamiast `null`.** `src/DTO/Casts/PaymentProfileDto.php` nie ustawia `protected bool $allowNull`, więc obowiązuje domyślne `false` (`src/DTO/BaseDto.php:16`). W `castAttributes` (`src/DTO/BaseDto.php:26-28`) dla brakującego klucza wykonuje się `castAttribute($cast, null)`, a wczesny return dla `null` działa wyłącznie przy `allowNull` (linie 39-41). W efekcie `(int) null = 0`, `(bool) null = false` nadpisują zadeklarowane defaulty `null` z linii 40-43. To sprzeczne z PHPDoc `@property-read int|null $month`, `int|null $year`, `bool|null $isActive` (linie 19-22), a dla `isActive` oznacza, że brak pola jest nieodróżnialny od profilu nieaktywnego.
- **[ROZBIEŻNOŚĆ] P3-3 — `isActive` jako `bool` zamiast `integer` 0/1.** `src/DTO/Casts/PaymentProfileDto.php:51` vs `api-components.json` → `schemas.paymentProfile.properties.isActive` (`type: integer`, `enum: [0,1]`). Semantycznie równoważne i wygodniejsze w PHP; różnica dotyczy typu zwracanego oraz round-tripu `toArray()` (zwróci `true`/`false`, nie `1`/`0`).
- **[ROZBIEŻNOŚĆ] P3-4 — `profile` bez typu wyliczeniowego.** Spec ma enum `["ONE_CLICK","RECURRING"]` (`schemas.paymentProfile.properties.profile`), repo trzyma surowy string (`src/DTO/Casts/PaymentProfileDto.php:44`, PHPDoc linia 23). W `src/Types/` nie ma odpowiedniego enuma — istniejący `WidgetType` (`src/Types/WidgetType.php:9-11`) używa małych liter `oneclick`/`recurring` i dotyczy widżetu Paywall, więc nie jest wymienny. Uwaga: dokumentacja ING sama jest tu niespójna — `ing-pay-paywall-topics/05-notyfikacje.md` dla tego samego pola `profile` podaje wartości `oneclick`, `recurring` (małe litery), a API — `ONE_CLICK`, `RECURRING`. Pozostawienie `string` faktycznie obsługuje oba warianty; wprowadzenie enuma wymagałoby ustalenia, który jest prawdziwy.
- **[BRAK] P3-5 — brak enuma dla `profileType` (`FIRST`/`STANDARD`).** `api-components.json` → `schemas.profileType` definiuje `enum: ["FIRST","STANDARD"]` dla wariantu *OneClick i rekurencyjna bez profilu* (opisanego w `ing-pay-api-topics/08-platnosci-kartowe-oneclick-i-rekurencyjne.md`). W `src/Types/` nie ma odpowiednika, a `src/DTO/Api/TransactionDto.php` (schemat `transaction`, poza moim zakresem) jest miejscem, w którym to pole jest używane. Dla pięciu endpointów grupy `profile` `profileType` nie występuje — zgłaszam jako lukę domenową do weryfikacji w sekcji transakcji.
- **[ROZBIEŻNOŚĆ] P3-6 — `paymentProfileId` w ścieżce bez walidacji UUID.** `src/Lib/Url.php:74-84`. Spec: `maxLength 36`, `format: uuid`, `pattern: ^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$`. Niepoprawna wartość da 404/422 dopiero z API, a oba te kody są przez `validateResponse` połykane.

---

## 4. `DELETE /{merchantId}/profile/id/{paymentProfileId}` — Dezaktywacja (DELETE)

**Werdykt: ZGODNE** — metoda, URL i parametry odpowiadają specyfikacji; sama specyfikacja nie definiuje dla tej operacji żadnej odpowiedzi.

### Metoda i URL

| element | spec | repo | status |
|---|---|---|---|
| metoda | `DELETE` | `Api::deactivateProfile` → `->delete($url)` (`src/Lib/Api.php:126`) | ZGODNE |
| URL | `/{merchantId}/profile/id/{paymentProfileId}` | `Url::createProfileIdUrl()` (`src/Lib/Url.php:74-84`), ten sam co dla GET | ZGODNE |

### Parametry ścieżki

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `merchantId` | string, required (path item) | z `Config` | ZGODNE |
| `paymentProfileId` | string, required, maxLength 36, uuid (path item) | bez walidacji (`src/Lib/Url.php:74`) | ROZBIEŻNOŚĆ (jak P3-6) |

### Request body / Response

| element | spec | repo | status |
|---|---|---|---|
| request body | brak (operacja nie ma `requestBody`) | `->delete($url)` bez body (`src/Lib/Api.php:126`) | ZGODNE |
| response | **operacja nie ma klucza `responses`** w `ing-pay-api.json`; w `components.responses` istnieje niepodpięte `delete-profile-response-200` = `{paymentProfile: paymentProfile}` z `isActive: 0` w przykładzie | `ProfileResponseDto` = `{paymentProfile}` (`src/Lib/Api.php:129`) | ZGODNE z komponentem |

### Ustalenia

- **[INFO] P4-1 — luka w samej specyfikacji, nie w repo.** Operacja `DELETE /{merchantId}/profile/id/{paymentProfileId}` w `ing-pay-api.json` ma wyłącznie klucze `tags` i `summary` — nie deklaruje ani `responses`, ani `parameters` (te dziedziczy z path item). Komponent `components.responses.delete-profile-response-200` istnieje, ale nie jest przez nic referencjonowany. Repo zwraca `ProfileResponseDto`, co dokładnie odpowiada temu osieroconemu komponentowi (`{paymentProfile}`, przykład z `isActive: 0`), więc wybór jest poprawny — ale nie da się tego rozstrzygnąć na podstawie samego pliku operacji.
- **[ROZBIEŻNOŚĆ] P4-2 — `Api::deactivateProfile` nie odróżnia profilu nieistniejącego od zdezaktywowanego.** `src/Lib/Api.php:122-130` z `validateResponse` obsługującym tylko 422/500 (`src/Lib/Api.php:244-248`). Odpowiedź 404 (tabela w `ing-pay-api-topics/05-kody-odpowiedzi-http.md`) przejdzie przez `default => null` i zwróci `ProfileResponseDto` z `paymentProfile === null` — nieodróżnialne od sukcesu z pustym body.
- **[INFO] P4-3 — oba warianty dezaktywacji zwracają ten sam typ.** `deactivateProfile` (DELETE) i `deactivateProfileByPost` (POST) zwracają `ProfileResponseDto` (`src/Lib/Api.php:124` i `159`), co jest spójne z identycznymi komponentami `delete-profile-response-200` i `deactivate-profile-response-200`.
- **[INFO] P4-4 — nagłówek `Content-Type: application/json` wysyłany przy DELETE bez body.** `Api::request()` ustawia `Http::asJson()` (`src/Lib/Api.php:228`) dla wszystkich metod. Specyfikacja nie określa wymagań dla żądań bez body; nie udało się rozstrzygnąć bez wywołania sandboxa, czy ING to akceptuje. Istniejący test (`tests/Lib/ApiTest.php:99-105`) używa `Http::fake`, więc niczego tu nie dowodzi.

---

## 5. `POST /{merchantId}/profile/deactivate` — Dezaktywacja (POST)

**Werdykt: ZGODNE** — metoda, URL i jedyne pole body odpowiadają specyfikacji.

### Metoda i URL

| element | spec | repo | status |
|---|---|---|---|
| metoda | `POST` | `Api::deactivateProfileByPost` → `->post($url, $dto->toArray())` (`src/Lib/Api.php:162`) | ZGODNE |
| URL | `/{merchantId}/profile/deactivate` | `Url::createProfileDeactivateUrl()` (`src/Lib/Url.php:125-134`) | ZGODNE |

### Parametry ścieżki

| pole | spec (typ, required) | repo | status |
|---|---|---|---|
| `merchantId` | string, required | z `Config` (`src/Lib/Url.php:130`) | ZGODNE |

### Request body — schemat `deactivate-profile`

| pole | spec (typ, required, ograniczenia) | repo | status |
|---|---|---|---|
| `paymentProfileId` | string, **required**, maxLength 36, format uuid, pattern UUID v4 | `DeactivateProfileDto` PHPDoc `string` (`src/DTO/Api/DeactivateProfileDto.php:12`, `23`); brak castu, brak walidacji | ZGODNE (pole) / ROZBIEŻNOŚĆ (walidacja) |

Pól nadmiarowych brak — DTO ma dokładnie jedną właściwość, tak jak schemat.

### Response

| element | spec | repo | status |
|---|---|---|---|
| response | **operacja nie ma klucza `responses`**; niepodpięte `components.responses.deactivate-profile-response-200` = `{paymentProfile: paymentProfile}` | `ProfileResponseDto` (`src/Lib/Api.php:165`) | ZGODNE z komponentem |

### Ustalenia

- **[INFO] P5-1 — jak P4-1: operacja w specyfikacji nie deklaruje żadnej odpowiedzi.** `ing-pay-api.json` → `paths./{merchantId}/profile/deactivate.post` ma klucze `tags`, `summary`, `parameters`, `requestBody` — bez `responses`. Osierocony komponent `deactivate-profile-response-200` zwraca `{paymentProfile}` i repo się z nim zgadza.
- **[ROZBIEŻNOŚĆ] P5-2 — brak walidacji UUID dla `paymentProfileId`.** `src/DTO/Api/DeactivateProfileDto.php:26-29` przekazuje atrybuty bez castów (`casts` nie jest zadeklarowane, `allowNull` pozostaje `false`, więc `BaseDto::castAttributes` nie modyfikuje tablicy — `src/DTO/BaseDto.php:24-35`). Fabryka generuje poprawny UUID (`src/Factories/Api/DeactivateProfileDtoFactory.php:17`), ale to tylko dane testowe.
- **[ROZBIEŻNOŚĆ] P5-3 — ta sama luka w obsłudze kodów błędów co w P4-2.** `src/Lib/Api.php:163` → `validateResponse` bez gałęzi dla 400/401/403/404 (`src/Lib/Api.php:244-248`).

---

## Ustalenia przekrojowe

- **[BŁĄD] PX-1 — `Api::validateResponse` ignoruje wszystkie kody poza 422 i 500.** `src/Lib/Api.php:236-249`. Dla grupy `profile` oznacza to w szczególności przemilczenie udokumentowanego 400 na `POST /transaction/profile` (P1-2) oraz 404 na obu endpointach dezaktywacji i obu GET-ach. Globalna tabela kodów (`ing-pay-api-topics/05-kody-odpowiedzi-http.md`) wymienia 400, 401, 403, 404, 406, 422, 500, 503, 504 — repo reaguje na dwa z dziewięciu. Dodatkowo `$response->ok()` w Laravelu oznacza dokładnie status 200, więc każdy inny 2xx też trafia do `match` i jest połykany przez `default => null`.
- **[INFO] PX-2 — pokrycie enumów odpowiedzi jest kompletne, więc `::from()` nie rzuci na danych ze specyfikacji.** `src/DTO/BaseDto.php:58` używa `$castType::from($value)` (nie `tryFrom`), co dla wartości spoza enuma kończy się `ValueError`. Sprawdziłem komplet wartości z `api-components.json` dla `status`, `type`, `source`, `currency`, `paymentMethod`, `paymentMethodCode` — wszystkie są pokryte przez odpowiednie enumy w `src/Types/`, które są ich nadzbiorami. Ryzyko istnieje tylko przy przyszłym rozszerzeniu API — czego sama specyfikacja się spodziewa (`schemas.additionalProperties`: *„Twój schemat walidacyjny odpowiedzi API powinien zawsze dopuszczać nowe parametry”*).
- **[BRAK] PX-3 — brak dokumentacji konsumenckiej dla całej grupy `profile`.** `docs/api.md` ma 6 linii i odsyła do nieaktualnych źródeł (`imojeapi.docs.apiary.io`, `imojeapieng.docs.apiary.io`) zamiast do obowiązującego `bump.sh/pgw/doc/ing-pay-api`. `grep -rn -i profile docs/*.md README.md` nie zwraca nic. Zgodnie z `AGENTS.md` pliki bezpośrednio w `docs/` są wyłącznie dokumentacją użytkową — pięć zaimplementowanych metod API nie jest w niej opisanych. `docs/paywall.md:44-74` dokumentuje tylko tworzenie profilu widżetem.
- **[INFO] PX-4 — testy profili weryfikują wyłącznie klasę zwracanego obiektu.** `tests/Lib/ApiTest.php:34-40` (wspólna asercja `toBeInstanceOf`) dla przypadków w liniach 85-105 i 120-132. Żaden test nie sprawdza pojedynczego pola odpowiedzi profilu, żaden nie podstawia kształtu zgodnego ze specyfikacją, a `tests/Lib/ApiSpecTest.php` (112 linii) nie zawiera ani jednego przypadku dla profili — jego przypadki dotyczą `payment` i `transaction`. `tests/Lib/UrlTest.php:37-63` sprawdza wszystkie cztery URL-e profili i te asercje są poprawne względem specyfikacji.
- **[INFO] PX-5 — `OneClickDto` nie obsługuje dwóch opcjonalnych parametrów widżetu.** `src/DTO/Paywall/OneClickDto.php:17-34` pokrywa komplet parametrów wymaganych z `ing-pay-paywall-topics/08-widzet-dla-platnosci-kartami.md` (merchantId, serviceId, amount, currency, orderId, customerId, customerFirstName, customerLastName, customerEmail, signature) oraz opcjonalne customerPhone, orderDescription, urlSuccess, urlFailure, urlReturn, urlCancel, widgetType, validTo. Brakuje `data-invoice` i `data-multipayout` (oba opcjonalne, oba wliczane do sygnatury) — spójnie z `docs/paywall.md:76-82`, gdzie multiwypłaty i ING Księgowość są oznaczone jako *Not implemented*. Parametry konfiguracyjne (`data-locale`, `data-inline`, `data-element-id`, `data-element-event`) są spoza sygnatury i nie muszą być w DTO. `WidgetType` (`src/Types/WidgetType.php:9-11`) pokrywa komplet `oneclick`/`recurring`/`ecom3ds`. Poza tym pakiet nie ma helpera renderującego widżet — `src/Lib/Paywall.php` ma tylko `createTransaction()`, a `Environment::widgetUrl()` (`src/Types/Environment.php:35-41`) jest używane wyłącznie w przykładzie w `docs/paywall.md:71`.
- **[INFO] PX-6 — `CardDto` nie jest użyte w żadnym z pięciu endpointów profilu.** `src/DTO/Casts/CardDto.php` (pola firstName, lastName, number, month, year, cvv, wszystkie castowane na `string`) dotyczy transakcji rejestrującej (`POST /{merchantId}/transaction` z obiektem `card`), opisanej w `ing-pay-api-topics/08-platnosci-kartowe-oneclick-i-rekurencyjne.md` i wymagającej certyfikatu PCI DSS. Brakuje w nim pola `firstTransactionId`, które wg tego samego rozdziału zastępuje `cvv` przy obciążaniu karty bez profilu — rozstrzygnięcie należy do sekcji audytu obejmującej `POST /transaction`. Warto odnotować kontrast: w `CardDto` `month`/`year` są poprawnie `string`, a w `PaymentProfileDto` te same pola są `int` (P3-1).

---

## Podsumowanie ilościowe

| etykieta | liczba |
|---|---|
| `[BŁĄD]` | 8 (P1-1, P1-2, P2-1, P2-3, P3-1, P3-2, PX-1 oraz kształt `paymentProfiles` w tabeli sekcji 2) |
| `[BRAK]` | 3 (P2-2, P3-5, PX-3) |
| `[ROZBIEŻNOŚĆ]` | 12 (P1-4, P1-5, P1-6, P2-4, P3-3, P3-4, P3-6, P4-2, P5-2, P5-3 + brak walidacji `serviceId`/`paymentProfileId`/`orderId` w sekcji 1) |
| `[NADMIAR]` | 5 (P1-3 + `TransactionStatus`, `PaymentMethod`, `PaymentMethodCode`, `paymentProfile` w `ProfileResponseDto` dla cid) |
| `[INFO]` | 10 (P1-7, P1-8, P2-5, P4-1, P4-3, P4-4, P5-1, PX-2, PX-4, PX-5, PX-6) |

Endpointów niezaimplementowanych: **0** — wszystkie 5 ma odpowiednik w `src/Lib/Api.php`.
URL-i niezgodnych ze specyfikacją: **0** — wszystkie 4 metody `Url` dają dokładnie `server + path` ze specyfikacji.
Metod HTTP niezgodnych: **0**.
