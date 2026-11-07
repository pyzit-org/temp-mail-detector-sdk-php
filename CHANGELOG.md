# Changelog

## [1.0.0] - 2026-11-07 — Major Release (TMD Platform v1.0 Alignment)

### ⚠️ Breaking Changes
- **API Endpoints**: Migrated from `/v1/validate/...` to `/v1/tmd/check/`, `/v1/tmd/detailed/`, and `/v1/tmd/bulk/`.
- **Token Format**: Standardized on `pyzit_live_...` prefix.
- **Exceptions**:
  - HTTP 402 now throws `QuotaExceededError` (monthly quota exhausted).
  - HTTP 403 now throws `InsufficientScopeError` (token lacks required scope).
  - HTTP 400 with batch limits now throws `BatchLimitExceededError`.
- **DetailedResult**:
  - `reputationScore` is now an `int` on a `0..100` scale.
  - Replaced legacy mock models with flat `DetailedDetails` (`hasMx`, `mxRecords`, `hasA`, `aRecords`, `hasSpf`, `spfRecord`, `hasDmarc`, `dmarcRecord`).
- **BulkResult**:
  - `total` property replaces legacy `processed`.
  - `results` is now an array of `CheckResult` objects.

### ✨ New Features
- **Privacy Relay Support**: Full detection of privacy relay mailboxes (Apple Private Relay, Firefox Relay, etc.) across `CheckResult`, `DetailedResult`, and `BulkResult` via `isPrivacyRelay`, `relayProvider`, `providerName`, and `privacyRelayEmails()`.
- **Helper Methods**: Added `hasMx()`, `mxHostnames()`, `aIps()`, `shouldReject()`, `shouldFlag()` to `DetailedResult`.
- **Automatic Retries**: Exponential backoff with jitter on 429 and 5xx responses, respecting `Retry-After`.
- **Fail-Open Mode**: Configurable `failOpen` parameter returning safe clean result on network failures.

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