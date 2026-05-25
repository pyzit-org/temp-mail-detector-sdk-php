# Changelog

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