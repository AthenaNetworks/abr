# Changelog

## Unreleased

### Fixed

- The authentication GUID is now trimmed before it is sent. A GUID with surrounding whitespace, such as the trailing newline of a secrets file, was rejected by the ABR as "not recognised as a Registered Party", so every call threw `AuthenticationException`.
- Name search results now use the address flagged `isCurrentIndicator=Y` when a record lists more than one `mainBusinessPhysicalAddress`. Previously the first address was used, which could be a historical one.

### Tests

- Added real ABR responses as fixtures: a private company lookup, a cancelled sole trader, and a name search filtered to NSW.

## 1.0

- First release.
