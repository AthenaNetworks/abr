# athenanetworks/abr

A clean, modern, object-oriented PHP client for the Australian Business Register (ABR) [ABN Lookup web services](https://abr.business.gov.au/Tools/WebServices).

It hides the ABR's SOAP-era quirks (errors returned as HTTP 200, `0001-01-01` "null" dates, `Y`/`N` strings, every parameter mandatory) behind typed value objects and exceptions.

- PHP 8.3+, no `ext-soap` needed. It uses the HTTP POST binding and any PSR-18 client.
- Works standalone, or as a Laravel package (auto-discovered service provider, config and facade).
- ABNs and ACNs are checksum-validated locally, before any request is sent.

## Installation

```bash
composer require athenanetworks/abr
```

You need a PSR-18 HTTP client. Laravel apps already have Guzzle. For other projects, install one if you don't have it:

```bash
composer require guzzlehttp/guzzle
```

You also need a free authentication GUID from the ABR: [register here](https://abr.business.gov.au/Tools/WebServices).

## Quick start (standalone)

```php
use AthenaNetworks\Abr\AbrClient;

$abr = new AbrClient('your-authentication-guid');

$entity = $abr->lookupAbn('33 051 775 556');

$entity->name();                  // "TELSTRA CORPORATION LIMITED"
$entity->isActive();              // true
$entity->entityType->description; // "Australian Public Company"
$entity->acn?->formatted();       // "051 775 556"
$entity->isGstRegistered();       // true
$entity->gstRegisteredFrom();     // DateTimeImmutable 2000-07-01
$entity->address()?->state;       // State::VIC
$entity->address()?->postcode;    // "3000"
$entity->tradingNames();          // ["TELSTRA", ...]
$entity->currentBusinessNames();  // ["TELSTRA INFRACO", "YELLOW PAGES", ...]
```

To use a specific HTTP client (for example, to set a timeout), pass it with PSR-17 factories:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

$factory = new HttpFactory();
$abr = new AbrClient($guid, new Client(['timeout' => 10]), $factory, $factory);
```

> The ABR often takes 1–4 seconds to answer a single lookup. Set a sensible timeout, and consider caching results.

## Laravel

The service provider and the `Abr` facade are registered automatically. Add your GUID to `.env`:

```dotenv
ABR_GUID=your-authentication-guid
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=abr-config
```

Then inject the contract or use the facade:

```php
use AthenaNetworks\Abr\Contracts\AbrClient;
use AthenaNetworks\Abr\Laravel\Facades\Abr;

public function show(AbrClient $abr, string $abn)
{
    return $abr->lookupAbn($abn);   // JSON-serialisable summary
}

Abr::searchByName('Athena Networks');
```

In tests, swap in a fake: `Abr::swap($fake)`, or bind your own implementation of `AthenaNetworks\Abr\Contracts\AbrClient`.

## API

### Lookups

```php
$abr->lookupAbn('51824753556');                    // BusinessEntity
$abr->lookupAbn(Abn::fromString('51 824 753 556'));
$abr->lookupAbn('51824753556', withHistory: true);  // include historical names, addresses, statuses
$abr->lookupAcn('005 749 986');                     // look up by ACN / ARBN / ARSN
```

A `BusinessEntity` exposes the full record as typed, readonly properties. Each one is a list, current record first. Historical records appear only when you ask for history.

| Property | Type |
|---|---|
| `abn`, `acn` | `Abn`, `?Acn` |
| `entityType` | `EntityType` (`code`, `description`, `isIndividual()`, `isCompany()`, `isTrust()`) |
| `statusHistory` | `list<StatusRecord>` (`AbnStatus` + `Period`) |
| `gstRegistrations` | `list<Period>` |
| `legalNames` | `list<IndividualName>`, for sole traders |
| `mainNames`, `mainTradingNames`, `otherTradingNames`, `businessNames` | `list<OrganisationName>` |
| `addresses` | `list<PhysicalAddress>` (`State`, postcode) |
| `dgrEndorsements`, `dgrFunds` | Deductible Gift Recipient details |
| `charityTypes`, `taxConcessions`, `acncRegistrations` | Charity details |
| `superannuationStatus` | `?SuperannuationStatus` |
| `recordLastUpdated` | `?DateTimeImmutable` |

Every dated record has a `Period` with nullable `from` and `to` dates, plus `isCurrent()` and `contains($date)`. The ABR's `0001-01-01` sentinel becomes `null`.

There are also convenience methods: `name()`, `status()`, `isActive()`, `isIndividual()`, `isGstRegistered()`, `gstRegisteredFrom()`, `address()`, `tradingNames()`, `currentBusinessNames()`, `isDgrEndorsed()`, `isCharity()` and `isAcncRegistered()`.

### Name search

```php
use AthenaNetworks\Abr\Query\NameQuery;
use AthenaNetworks\Abr\SearchWidth;
use AthenaNetworks\Abr\State;

$results = $abr->searchByName('Athena Networks');   // a plain string works too

$results = $abr->searchByName(
    NameQuery::for('Athena Networks')
        ->inStates(State::NSW, State::ACT)   // default: all states
        ->inPostcode('2600')
        ->businessNames()->tradingNames()    // default: legal, trading and business names
        ->activeOnly()
        ->width(SearchWidth::Narrow)
        ->minimumScore(80)
        ->limit(20),
);

foreach ($results as $result) {
    $result->abn;          // Abn
    $result->name;         // the name that matched
    $result->nameType;     // NameType::BusinessName, ::MainName, ::LegalName, ...
    $result->score;        // 0-100
    $result->abnStatus;    // AbnStatus::Active
    $result->state;        // ?State
    $result->postcode;
}

$results->first();
$results->uniqueByAbn();   // one result per ABN
$results->filter(fn ($r) => $r->isActive());
$results->exceedsMaximum;  // more matches than were returned
```

A search with no matches returns an empty `SearchResults`. It doesn't throw.

`NameQuery` is immutable: every method returns a new instance.

### ABN lists

These return an `AbnList` (countable, iterable `Abn` objects, `contains()`, `toStrings()`):

```php
use AthenaNetworks\Abr\Query\AbnStatusQuery;
use AthenaNetworks\Abr\Query\CharityQuery;

$abr->searchByPostcode('2600');   // all active ABNs in a postcode

$abr->searchByStatus(
    AbnStatusQuery::inPostcode('2600')->activeOnly()->gstRegisteredOnly()->entityType('PRV'),
);

$abr->searchCharities(
    CharityQuery::create()->inState(State::NSW)->charityType('PBI'),
);
```

These can return thousands of ABNs. The ABR has no paging.

### Identifiers

```php
use AthenaNetworks\Abr\Abn;
use AthenaNetworks\Abr\Acn;

Abn::isValid('51 824 753 556');        // true (no network call)
Abn::fromString('51824753556');        // throws InvalidIdentifierException if invalid
Abn::tryFromString('nope');            // null
Abn::fromString('51824753556')->formatted();   // "51 824 753 556"
Acn::fromString('005749986')->formatted();     // "005 749 986"
```

### Errors

Every exception extends `AthenaNetworks\Abr\Exceptions\AbrException`.

| Exception | When |
|---|---|
| `InvalidIdentifierException` | The ABN or ACN is malformed (thrown locally), or the ABR rejects it |
| `InvalidQueryException` | Empty name, bad postcode, out-of-range score or limit (always thrown locally) |
| `NotFoundException` | A lookup for a well-formed ABN or ACN that isn't on the register |
| `AuthenticationException` | The GUID is missing or not recognised |
| `ServiceException` | Any other ABR error, including HTTP 5xx (has `abrCode` and `httpStatus`) |
| `TransportException` | Network failure: DNS, connection, timeout or TLS |
| `UnexpectedResponseException` | The response isn't a valid ABR payload |

## Operations used

| Method | ABR operation |
|---|---|
| `lookupAbn` | `SearchByABNv202001` |
| `lookupAcn` | `SearchByASICv201408` (the latest ACN version) |
| `searchByName` | `ABRSearchByNameAdvancedSimpleProtocol2017`, because the 2023 version is currently disabled by the ABR |
| `searchByPostcode` | `SearchByPostcode` |
| `searchByStatus` | `SearchByABNStatus` |
| `searchCharities` | `SearchByCharity` |

## ABR API shortcomings

The problems found in the ABR service while building this package are recorded, with evidence, in [docs/abr-api-shortcomings.md](docs/abr-api-shortcomings.md).

## Testing

```bash
composer test                        # offline, against recorded fixtures
ABR_GUID=your-guid composer test:live   # against the real ABR
```

## Licence

MIT
