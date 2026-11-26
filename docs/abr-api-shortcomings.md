# ABR web services: shortcomings found

Problems with the ABN Lookup web services (`https://abr.business.gov.au/abrxmlsearch/abrxmlsearch.asmx`), found while building this client. Each entry has enough detail to report to the ABR.

All observations were made on 2026-11-26 using the HTTP GET/POST bindings unless stated otherwise.

## 1. `ABRSearchByNameAdvanced2023` is advertised but disabled

The operation appears in the WSDL and the schema (`ExternalRequestNameSearchAdvanced2023`), but every binding rejects it:

- HTTP GET/POST `ABRSearchByNameAdvancedSimpleProtocol2023` returns **HTTP 500**, `text/plain`: `This feature is currently not available !`
- SOAP `ABRSearchByNameAdvanced2023` returns a SOAP Fault: `Server was unable to process request. ---> This feature is currently not available!`
- The RPC endpoint (`abrxmlsearchRPC`) does the same.

The operations page also doesn't list the 2023 version, so the WSDL and the published page disagree. Clients have to fall back to `ABRSearchByNameAdvancedSimpleProtocol2017`.

## 2. Optional parameters are actually mandatory, and leaving one out returns a plain-text 500

Leaving out a parameter the documentation treats as optional (for example `postcode` on a name search) returns **HTTP 500**, `text/plain`: `Missing parameter: postcode.`

Every parameter has to be sent, even with an empty value. This error isn't returned in the normal XML `<exception>` envelope, so clients need a second error path.

## 3. Errors come back as HTTP 200, with codes too coarse to act on

Business errors come back as **HTTP 200** with a `<response><exception>` element. The `exceptionCode` values don't tell different problems apart:

| Situation | exceptionCode | exceptionDescription |
|---|---|---|
| Unknown GUID | `WEBSERVICES` | `The GUID entered is not recognised as a Registered Party` |
| Malformed ABN | `WEBSERVICES` | `Search text is not a valid ABN or ACN` |
| ABN not found / no name matches | `Search` | `No records found` |

An authentication failure and invalid input share one code, so clients have to match on the English description text, which is fragile.

## 4. Sentinel date `0001-01-01` instead of null, applied inconsistently

Missing dates are sent as `0001-01-01` (for example `effectiveTo` on a current status, `replacedFrom`, and `dateRegisterLastUpdated` on error responses). They aren't left out or marked `xsi:nil`.

Within the same response, `OrganisationName` elements (`mainName`, `businessName`, …) do **leave out** `effectiveTo` when there is no end date, while `IndividualName` (`legalName`) uses `0001-01-01`. The schema even defines an `effectiveToBlank` choice that never appears.

## 5. Every response echoes the authentication GUID

Every response repeats the request, including `<authenticationGUID>`. Any client that logs response bodies leaks its credential into those logs.

## 6. Invalid input is accepted silently

- `SearchByPostcode?postcode=abc` returns HTTP 200 with `numberOfRecords` = 0. It doesn't reject the input.
- Empty filter values on a name search silently become defaults: all name types `Y`, all states `Y`, `activeABNsOnly` = `N`, `searchWidth` = `Typical`, `minimumScore` = 0, `maxSearchResults` = 0. None of these defaults are documented on the operation.

## 7. ABN-list operations have no paging

`SearchByPostcode`, `SearchByABNStatus` and `SearchByCharity` return every match in one response, with no paging or limit:

- `SearchByPostcode?postcode=2913`: about 690 KB
- `SearchByABNStatus` for postcode 2600: 5,483 ABNs
- `SearchByCharity` with only `state=NSW`: 22,662 ABNs

## 8. ACN lookups lag behind ABN lookups

The newest ACN lookup is `SearchByASICv201408`. There is no `SearchByASICv202001`, so the Approved Worker Entitlement Fund (AWEF) data that `SearchByABNv202001` returns can't be fetched by ACN.

## 9. Naming and typing are inconsistent

- The request parameter is `authenticationGuid`, but the echoed element is `authenticationGUID`.
- `SearchByABNStatus` takes `activeABNsOnly`, but the echoed request calls it `activeABNs`.
- Booleans are the strings `Y` and `N`, not `xs:boolean`.
- `dgrFundName` records include a `score` element (always `0`) that only means something in search results.

## 10. Schema download is not portable

`AbnLookupSchema.zip` uses Windows backslash path separators (`XSD\ABNLookup_schema.xsd`), so `unzip` on macOS and Linux warns about it. The XSD also starts with a UTF-8 BOM.

## 11. Valid filter codes are not documented, and unknown codes are accepted silently

`SearchByCharity` (`charityTypeCode`, `concessionTypeCode`) and `SearchByABNStatus` (`entityTypeCode`) take code values, but the operation pages don't list the valid codes. An unrecognised code isn't rejected: it returns HTTP 200 with `numberOfRecords` = 0.

| Request (postcode 2600) | numberOfRecords |
|---|---|
| no codes | 164 |
| `charityTypeCode=PBI` | 31 |
| `charityTypeCode=ZZZ` | 0 |
| `concessionTypeCode=GST` | 0 (the obvious guess, from the "GST Concession" label in entity responses) |
| `concessionTypeCode=ZZZ` | 0 |
| `SearchByABNStatus` `entityTypeCode=ZZZ` | 0 |
| `SearchByCharity` `state=XX` | 0 |

A typo can't be told apart from "no matches".

## 12. An empty postcode is treated as a real value

`SearchByPostcode?postcode=` (empty) returns **20,816** ABNs, and `SearchByABNStatus` with an empty postcode returns the same 20,816. That looks like every ABN with a blank postcode, not an error. An empty postcode should be rejected.

## 13. Single-record lookups are slow

One `SearchByABNv202001` call for one ABN (33051775556, no history) took **1.1–3.9 s** of server time across five back-to-back requests. TCP and TLS setup took about 0.1 s, so the delay is on the server. A "No records found" lookup comes back in about 0.2 s, which suggests the time is spent building the record, not on network or authentication.
