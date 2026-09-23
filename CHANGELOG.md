# Changelog

## 1.0.0 — Major Release (API Architecture Update)

### Breaking Changes
- **Updated API architecture**: All plans can now access all endpoints (check, detailed, bulk)
- **New quota model**: Separate simple and detailed quotas per plan
- **Token format**: New tokens use `pyzit_live_...` prefix
- **Exception hierarchy**: Enhanced with more specific exception types
- **Model structure**: DetailedResult now includes nested comprehensive analysis data

### New Features
- **Enhanced DetailedResult**: Added recommendation field ('accept', 'challenge', 'reject')
- **Helper methods**: Added `shouldReject()`, `shouldChallenge()`, `shouldAccept()` to DetailedResult
- **Risk level normalization**: Risk levels and recommendations are now normalized to lowercase
- **Reputation score type**: Changed from int to float for more precise scoring
- **Bulk result structure**: Changed from CheckResult array to email => boolean mapping
- **New exception types**: Added `ScopeException` and `PlanRequiredException` for specific error handling
- **Retry logic**: Automatic retry with exponential backoff for 429 and 5xx errors
- **Fail-open configuration**: Added `failOpen` option to return clean results on network errors
- **HTTP client improvements**: Enhanced error parsing and status code handling

### API Changes
- **402 status**: Now throws `PlanRequiredException` instead of `QuotaExceededError`
- **403 status**: Enhanced handling to distinguish between scope errors and plan requirements
- **429 status**: Default retry-after changed from 1 to 60 seconds
- **DetailedResult structure**: Complete restructure with nested objects (reputation, signals, dnsIntelligence, stability)

### Bug Fixes
- Fixed scope parsing to handle multiple error message formats
- Fixed bulk result to use `processed` instead of `total` property
- Fixed detailed result reputation score type from int to float
- Fixed helper methods to use nested object structure correctly
- Fixed MX record handling to support both array and string formats

### Documentation
- Added comprehensive README.md with usage examples and framework integrations
- Added internal DOCUMENTATION.md with complete API reference
- Updated error handling documentation with specific exception types
- Added framework integration examples (Laravel, Symfony, WordPress)

### Testing
- Increased test coverage from 81 to 95 tests
- Updated test fixtures to match new API response structure
- Added tests for new exception types and error handling
- Added tests for helper methods and normalization
- Fixed Docker configuration for local development

### Dependencies
- No new external dependencies added
- Maintains zero-dependency policy (ext-curl, ext-json, ext-mbstring only)
- PHP requirement remains 8.1+

### Migration Notes
- Update API token format to use `pyzit_live_...` prefix
- Review exception handling for new exception types
- Update quota monitoring for new quota model
- Test detailed() calls with new nested structure
- Update bulk result processing to use new mapping structure

## 0.1.1 — documentation update

- Replaced all `make`-based development commands with `dev.ps1`
- Updated Docker setup and workflow instructions for PowerShell usage
- Added `dev.ps1` command reference section
- Updated machine setup instructions to use the new workflow

## 0.1.0 — initial release

- `TempMailClient` with `check()`, `detailed()`, `bulk()` methods
- Full typed model classes: `CheckResult`, `DetailedResult`, `BulkResult`
- Nested models: `DetailedDetails`, `DnsIntelligence`, `MxRecord`, `Signals`, `ReputationDetail`, `StabilityInfo`
- Exception hierarchy: `PyzitException`, `AuthenticationException`, `ScopeException`, `PlanRequiredException`, `RateLimitException`, `ApiException`, `TimeoutException`
- Zero dependencies (ext-curl, ext-json only)
- PHP 8.1+ with readonly properties and constructor promotion
- 81 tests, 167 assertions