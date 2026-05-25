# pyzit/tempmail — PHP SDK

Official PHP client for the [Pyzit Disposable Email Detector API](https://temp-mail-detector.pyzit.com).
Detect throwaway and temporary email addresses before they reach your database.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-81%20passing-brightgreen)](#testing)

```php
$client = new TempMailClient('YOUR_API_TOKEN');
$result = $client->check('user@mailnator.com');

if ($result->isDisposable) {
    throw new \RuntimeException('Disposable emails are not allowed.');
}
```

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Authentication](#authentication)
- [Methods](#methods)
  - [check()](#check--free-tier)
  - [detailed()](#detailed--pro-tier)
  - [bulk()](#bulk--business-tier)
- [Response Models](#response-models)
- [Error Handling](#error-handling)
- [Framework Integration](#framework-integration)
- [Configuration](#configuration)
- [Testing](#testing)
- [Project Structure](#project-structure)

---

## Requirements

| Requirement | Version  |
|-------------|----------|
| PHP         | ≥ 8.1    |
| ext-curl    | any      |
| ext-json    | any      |

No other runtime dependencies.

---

## Installation

```bash
composer require pyzit/tempmail
```

---

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use Pyzit\TempMail\TempMailClient;

$client = new TempMailClient('YOUR_API_TOKEN');

$result = $client->check('user@example.com');

echo $result->email;        // "user@example.com"
echo $result->isDisposable; // false
echo $result->status;       // "clean"
echo $result->isClean();    // true
```

Get your API token from the [Pyzit dashboard](https://temp-mail-detector.pyzit.com).

---

## Authentication

Pass your API token as the first argument to `TempMailClient`:

```php
$client = new TempMailClient('YOUR_API_TOKEN');
```

Keep your token secret. The recommended approach is to load it from an environment variable:

```php
$client = new TempMailClient($_ENV['PYZIT_TOKEN']);
// or
$client = new TempMailClient(getenv('PYZIT_TOKEN'));
```

---

## Methods

### check() — Free Tier

Quick disposable check. Returns in milliseconds.

```php
$result = $client->check('user@example.com');
```

**Parameters**

| Name    | Type     | Description               |
|---------|----------|---------------------------|
| `$email` | `string` | The email address to check |

**Returns** [`CheckResult`](#checkresult)

```php
$result = $client->check('user@mailnator.com');

echo $result->email;         // "user@mailnator.com"
echo $result->isDisposable;  // true
echo $result->status;        // "disposable"
echo $result->isClean();     // false  (convenience method)
```

---

### detailed() — Pro Tier

Full DNS, reputation, and signal analysis. Use this when you need to know *why* an email is suspicious — not just *that* it is.

```php
$result = $client->detailed('user@example.com');
```

**Parameters**

| Name    | Type     | Description                   |
|---------|----------|-------------------------------|
| `$email` | `string` | The email address to analyse  |

**Returns** [`DetailedResult`](#detailedresult)

```php
$result = $client->detailed('user@example.com');

echo $result->reputationScore;  // 0.0 – 1.0
echo $result->riskLevel;        // "low" | "medium" | "high"
echo $result->recommendation;   // "accept" | "review" | "reject"

if ($result->shouldReject()) {
    throw new \RuntimeException('Email rejected: ' . $result->riskLevel);
}

// DNS intelligence
$dns = $result->details->dnsIntelligence;
echo $dns->hasMx;     // true/false
echo $dns->hasSpf;    // true/false
echo $dns->hasDmarc;  // true/false

foreach ($dns->mxRecords as $mx) {
    echo $mx->priority;    // 5
    echo $mx->exchange;    // "mail.example.com"
    echo implode(', ', $mx->ips); // "1.2.3.4"
}

// Signals — what triggered the result
$signals = $result->details->signals;
print_r($signals->positive); // ["established_domain", "has_spf"]
print_r($signals->negative); // ["no_mx_records", "new_domain"]
print_r($signals->neutral);  // ["limited_history"]

// Domain stability
$stability = $result->details->stability;
echo $stability->domainAgeDays;   // 0
echo $stability->isNewDomain;     // true
echo $stability->stabilityScore;  // 0.0 – 1.0
print_r($stability->riskFactors); // ["newly_observed_domain"]
```

> **Requires** the Pro plan. Throws [`PlanRequiredException`](#planrequiredexception) if your account does not have access.

---

### bulk() — Business Tier

Validate up to 100 emails in a single API call. Far more efficient than looping `check()`.

```php
$result = $client->bulk(['a@example.com', 'b@mailnator.com', 'c@github.com']);
```

**Parameters**

| Name      | Type       | Description                           |
|-----------|------------|---------------------------------------|
| `$emails` | `string[]` | Array of email addresses (max 100)    |

**Returns** [`BulkResult`](#bulkresult)

```php
$result = $client->bulk([
    'hi@pyzit.com',
    'user@mailnator.com',
    'support@github.com',
    'fake@temp-mail.org',
]);

echo $result->processed; // 4

// Full map: email → is_disposable
var_dump($result->results);
// [
//   "hi@pyzit.com"       => false,
//   "user@mailnator.com" => true,
//   "support@github.com" => false,
//   "fake@temp-mail.org" => true,
// ]

// Convenience helpers
$blocked = $result->disposableEmails(); // ["user@mailnator.com", "fake@temp-mail.org"]
$allowed = $result->cleanEmails();      // ["hi@pyzit.com", "support@github.com"]
```

> **Requires** the Business plan. Throws [`PlanRequiredException`](#planrequiredexception) if your account does not have access.

---

## Response Models

All models are immutable value objects with `readonly` properties. There is no JSON decoding to do — the SDK handles it.

### CheckResult

Returned by `check()`.

| Property       | Type     | Description                              |
|----------------|----------|------------------------------------------|
| `$email`       | `string` | The email address that was validated     |
| `$isDisposable`| `bool`   | `true` if the email is disposable        |
| `$status`      | `string` | `"clean"` or `"disposable"`             |

**Methods**

| Method       | Returns | Description                          |
|--------------|---------|--------------------------------------|
| `isClean()`  | `bool`  | Convenience: `!$this->isDisposable`  |

---

### DetailedResult

Returned by `detailed()`.

| Property          | Type              | Description                              |
|-------------------|-------------------|------------------------------------------|
| `$email`          | `string`          | The email address                        |
| `$domain`         | `string`          | The domain portion                       |
| `$isDisposable`   | `bool`            | Whether the email is disposable          |
| `$status`         | `string`          | `"clean"` or `"disposable"`             |
| `$reputationScore`| `float`           | `0.0` (bad) to `1.0` (trusted)          |
| `$riskLevel`      | `string`          | `"low"`, `"medium"`, or `"high"`        |
| `$recommendation` | `string`          | `"accept"`, `"review"`, or `"reject"`   |
| `$details`        | `DetailedDetails` | Nested analysis object                   |

**Methods**

| Method          | Returns | Description                               |
|-----------------|---------|-------------------------------------------|
| `shouldReject()`| `bool`  | `true` when `recommendation === "reject"` |

#### DetailedDetails

Accessible via `$result->details`.

| Property          | Type               | Description                   |
|-------------------|--------------------|-------------------------------|
| `$reputation`     | `ReputationDetail` | Reputation scoring            |
| `$signals`        | `Signals`          | Positive / negative signals   |
| `$dnsIntelligence`| `DnsIntelligence`  | DNS record analysis           |
| `$stability`      | `StabilityInfo`    | Domain age and stability      |

#### DnsIntelligence

| Property     | Type        | Description                         |
|--------------|-------------|-------------------------------------|
| `$hasMx`     | `bool`      | Domain has MX records               |
| `$mxRecords` | `MxRecord[]`| Array of MX record objects          |
| `$hasARecord`| `bool`      | Domain has an A record              |
| `$hasSpf`    | `bool`      | Domain has an SPF record            |
| `$hasDmarc`  | `bool`      | Domain has a DMARC record           |
| `$error`     | `?string`   | DNS lookup error message, or `null` |

#### MxRecord

| Property    | Type       | Description                           |
|-------------|------------|---------------------------------------|
| `$priority` | `int`      | MX priority (lower = higher priority) |
| `$exchange` | `string`   | Mail exchange hostname                |
| `$ips`      | `string[]` | Resolved IP addresses for this host   |

#### Signals

| Property    | Type       | Description                        |
|-------------|------------|------------------------------------|
| `$positive` | `string[]` | Signals that lower risk            |
| `$negative` | `string[]` | Signals that increase risk         |
| `$neutral`  | `string[]` | Informational signals              |

#### ReputationDetail

| Property               | Type     | Description                        |
|------------------------|----------|------------------------------------|
| `$reputationScore`     | `float`  | 0.0 – 1.0 reputation score        |
| `$isDisposable`        | `bool`   | Whether classified as disposable   |
| `$disposableConfidence`| `float`  | Confidence in the classification   |
| `$riskLevel`           | `string` | `"low"`, `"medium"`, or `"high"`  |
| `$recommendation`      | `string` | `"accept"`, `"review"`, `"reject"`|

#### StabilityInfo

| Property          | Type       | Description                         |
|-------------------|------------|-------------------------------------|
| `$stabilityScore` | `float`    | 0.0 – 1.0 domain stability score   |
| `$domainAgeDays`  | `int`      | Age of the domain in days           |
| `$isNewDomain`    | `bool`     | `true` if the domain is newly seen  |
| `$riskFactors`    | `string[]` | Risk factor identifiers             |

---

### BulkResult

Returned by `bulk()`.

| Property     | Type                  | Description                          |
|--------------|-----------------------|--------------------------------------|
| `$results`   | `array<string, bool>` | Map of email address → is_disposable |
| `$processed` | `int`                 | Number of emails processed           |

**Methods**

| Method               | Returns    | Description                          |
|----------------------|------------|--------------------------------------|
| `disposableEmails()` | `string[]` | Only the disposable email addresses  |
| `cleanEmails()`      | `string[]` | Only the clean email addresses       |

---

## Error Handling

All exceptions extend `PyzitException`, so you can catch everything in one block or handle specific cases:

```php
use Pyzit\TempMail\Exceptions\PyzitException;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\ScopeException;
use Pyzit\TempMail\Exceptions\PlanRequiredException;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\TimeoutException;

try {
    $result = $client->check('user@example.com');
} catch (AuthenticationException $e) {
    // HTTP 401 — invalid or missing API token
    // Fix: check your token in the Pyzit dashboard
    log_error($e->getMessage());

} catch (ScopeException $e) {
    // HTTP 403 — token is missing a required scope
    // Fix: enable the scope in your Pyzit dashboard → API Tokens
    log_error('Missing scope: ' . $e->getRequiredScope());

} catch (PlanRequiredException $e) {
    // HTTP 402/403 — endpoint requires a higher plan
    // Fix: upgrade your Pyzit subscription
    log_error('Need plan: ' . $e->getRequiredPlan());

} catch (RateLimitException $e) {
    // HTTP 429 — too many requests
    // Fix: back off and retry after $e->getRetryAfter() seconds
    sleep($e->getRetryAfter());
    $result = $client->check('user@example.com'); // retry

} catch (TimeoutException $e) {
    // cURL timeout exceeded
    // Fix: increase timeout or implement retry logic
    log_error('Request timed out');

} catch (ApiException $e) {
    // Unexpected HTTP error (5xx, unknown 4xx)
    log_error('API error ' . $e->getStatusCode() . ': ' . $e->getResponseBody());

} catch (PyzitException $e) {
    // Catch-all for any other SDK error
    log_error($e->getMessage());
}
```

### Exception Reference

| Exception                  | HTTP Status    | Attribute                           |
|----------------------------|----------------|-------------------------------------|
| `AuthenticationException`  | 401            | —                                   |
| `ScopeException`           | 403 (scope)    | `getRequiredScope(): string`        |
| `PlanRequiredException`    | 402 / 403      | `getRequiredPlan(): string`         |
| `RateLimitException`       | 429            | `getRetryAfter(): int` (seconds)    |
| `ApiException`             | 5xx / other    | `getStatusCode(): int`, `getResponseBody(): string` |
| `TimeoutException`         | —              | —                                   |

All of the above extend `PyzitException` which extends `\RuntimeException`.

### Fail-Open Pattern

Never block a legitimate user because your email validation API is temporarily down.
Always **fail open** on SDK errors:

```php
function isEmailAllowed(TempMailClient $client, string $email): bool
{
    try {
        $result = $client->check($email);
        return !$result->isDisposable;
    } catch (PyzitException) {
        // API error — let the request through rather than blocking real users
        return true;
    }
}
```

---

## Framework Integration

### Laravel

**Service Provider binding:**

```php
// AppServiceProvider::register()
$this->app->singleton(TempMailClient::class, fn() =>
    new TempMailClient(config('services.pyzit.token'))
);
```

**Middleware:**

```php
<?php
// app/Http/Middleware/BlockDisposableEmails.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\PyzitException;

class BlockDisposableEmails
{
    public function __construct(private TempMailClient $client) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $email = $request->input('email');

        if ($email) {
            try {
                $r = $this->client->check($email);
                if ($r->isDisposable) {
                    return response()->json(
                        ['message' => 'Disposable email addresses are not allowed.'],
                        422
                    );
                }
            } catch (PyzitException) {
                // Fail open — API issues should never block real users
            }
        }

        return $next($request);
    }
}
```

**Form Request validation rule:**

```php
<?php
// app/Rules/NotDisposableEmail.php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\PyzitException;

class NotDisposableEmail implements ValidationRule
{
    public function __construct(private TempMailClient $client) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $r = $this->client->check((string) $value);
            if ($r->isDisposable) {
                $fail('Disposable email addresses are not allowed.');
            }
        } catch (PyzitException) {
            // Fail open
        }
    }
}

// Usage in a form request:
// 'email' => ['required', 'email', new NotDisposableEmail(app(TempMailClient::class))],
```

---

### Symfony

**Service configuration (`config/services.yaml`):**

```yaml
services:
    Pyzit\TempMail\TempMailClient:
        arguments:
            $apiToken: '%env(PYZIT_TOKEN)%'
            $timeout: 10
```

**Validator Constraint:**

```php
<?php
// src/Validator/NotDisposableEmailValidator.php

namespace App\Validator;

use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\PyzitException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotDisposableEmailValidator extends ConstraintValidator
{
    public function __construct(private TempMailClient $client) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        try {
            $r = $this->client->check((string) $value);
            if ($r->isDisposable) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->addViolation();
            }
        } catch (PyzitException) {
            // Fail open — do not block submissions when the API is unavailable
        }
    }
}
```

---

### Plain PHP

```php
<?php

require 'vendor/autoload.php';

use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\PyzitException;

$client = new TempMailClient($_ENV['PYZIT_TOKEN']);

$email = $_POST['email'] ?? '';

try {
    $r = $client->check($email);
    if ($r->isDisposable) {
        http_response_code(422);
        echo json_encode(['error' => 'Disposable email addresses are not allowed.']);
        exit;
    }
} catch (PyzitException $e) {
    // Fail open
}

// Continue processing the valid email...
```

---

## Configuration

```php
$client = new TempMailClient(
    apiToken: $_ENV['PYZIT_TOKEN'],
    timeout:  15,                              // seconds, default: 10
    baseUrl:  'https://api-tempmail.pyzit.com/v1', // default, override for testing
);
```

| Parameter  | Type     | Default                                    | Description                  |
|------------|----------|--------------------------------------------|------------------------------|
| `$apiToken`| `string` | —                                          | Your Pyzit API token         |
| `$timeout` | `int`    | `10`                                       | cURL timeout in seconds      |
| `$baseUrl` | `string` | `https://api-tempmail.pyzit.com/v1`        | API base URL                 |

---

## Testing

### Running the test suite

```bash
composer install
composer test
```

Output:

```
PHPUnit 11.x

Exceptions       ✔ 31 tests
HttpClientError  ✔ 16 tests
Models           ✔ 17 tests
TempMailClient   ✔ 17 tests

OK (81 tests, 167 assertions)
```

### Testing your own code

The SDK is designed to be testable. In your own tests, inject a fake or mock `TempMailClient` rather than making real API calls.

**Using a hand-rolled fake:**

```php
<?php

use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Models\CheckResult;

class FakeTempMailClient extends TempMailClient
{
    public function __construct(private bool $disposable = false)
    {
        // Skip parent constructor — no real HTTP
    }

    public function check(string $email): CheckResult
    {
        return new CheckResult(
            email:        $email,
            isDisposable: $this->disposable,
            status:       $this->disposable ? 'disposable' : 'clean',
        );
    }
}

class RegistrationServiceTest extends TestCase
{
    public function testDisposableEmailIsRejected(): void
    {
        $service = new RegistrationService(new FakeTempMailClient(disposable: true));
        $this->expectException(\RuntimeException::class);
        $service->register('user@example.com');
    }

    public function testCleanEmailIsAccepted(): void
    {
        $service = new RegistrationService(new FakeTempMailClient(disposable: false));
        $this->assertNull($service->register('user@example.com'));
    }
}
```

**Using PHPUnit mocks:**

```php
$mock = $this->createMock(TempMailClient::class);
$mock->method('check')->willReturn(
    new CheckResult('user@mailnator.com', true, 'disposable')
);
```

---

## Project Structure

```
src/
├── TempMailClient.php         Main client — check(), detailed(), bulk()
├── HttpClient.php             Internal cURL layer — not part of public API
├── Exceptions/
│   ├── PyzitException.php     Base — catch all SDK errors with this
│   ├── AuthenticationException.php
│   ├── ScopeException.php
│   ├── PlanRequiredException.php
│   ├── RateLimitException.php
│   ├── ApiException.php
│   └── TimeoutException.php
└── Models/
    ├── CheckResult.php
    ├── DetailedResult.php
    ├── BulkResult.php
    ├── DetailedDetails.php
    ├── DnsIntelligence.php
    ├── MxRecord.php
    ├── Signals.php
    ├── ReputationDetail.php
    └── StabilityInfo.php

tests/
├── Fixtures.php               Shared test data
├── ExceptionsTest.php
├── ModelsTest.php
├── HttpClientErrorTest.php
└── TempMailClientTest.php
```

---

## API Reference

Full API documentation: [https://temp-mail-detector.pyzit.com/docs](https://temp-mail-detector.pyzit.com/docs)

| Endpoint                   | Method | Plan       | SDK method    |
|----------------------------|--------|------------|---------------|
| `/v1/validate/check/`      | POST   | Free       | `check()`     |
| `/v1/validate/detailed/`   | POST   | Pro        | `detailed()`  |
| `/v1/validate/bulk/`       | POST   | Business   | `bulk()`      |

---

## License

MIT — see [LICENSE](LICENSE) for details.

---

## Support

- **Documentation:** [https://temp-mail-detector.pyzit.com/docs](https://temp-mail-detector.pyzit.com/docs)
- **Email:** [hi@pyzit.com](mailto:hi@pyzit.com)
- **Issues:** [GitHub Issues](https://github.com/pyzit/pyzit-tempmail-php/issues)

---

## Docker (Recommended)

Docker is the recommended way to work with this SDK. It pins PHP and Composer to exact versions, so the project runs identically on any machine — your laptop, a new PC, a CI server, a teammate's computer. No PHP or Composer installation needed on the host.

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows / Mac) or Docker Engine (Linux)
- That's it.

### First-time setup

```bash
git clone https://github.com/pyzit/pyzit-tempmail-php.git
cd pyzit-tempmail-php

cp .env.example .env
# edit .env and add your PYZIT_TOKEN

make install   # builds the image + installs composer deps (~30s first time)
```

### Daily workflow

```bash
make test      # run the full test suite
make shell     # open bash inside the container — run any php/composer command
make example   # run example_usage.php against the real API
```

### All make commands

| Command                       | What it does                                     |
|-------------------------------|--------------------------------------------------|
| `make install`                | Build image + `composer install`                 |
| `make test`                   | Run all 81 PHPUnit tests                         |
| `make test-filter FILTER=Foo` | Run only tests matching `Foo`                    |
| `make shell`                  | Interactive bash shell inside the container      |
| `make example`                | Run `example_usage.php` (needs `PYZIT_TOKEN`)    |
| `make build`                  | Rebuild the Docker image from scratch            |
| `make clean`                  | Remove vendor volume + image (full reset)        |

### Running without Make

If you prefer raw docker commands:

```bash
# install deps
docker compose run --rm sdk "composer install"

# run tests
docker compose run --rm sdk "vendor/bin/phpunit --testdox"

# open a shell
docker compose run --rm --entrypoint bash sdk

# run a specific test file
docker compose run --rm sdk "vendor/bin/phpunit --testdox tests/ExceptionsTest.php"
```

### Moving to a new machine

```bash
# clone the repo
git clone https://github.com/pyzit/pyzit-tempmail-php.git
cd pyzit-tempmail-php

# copy your token
cp .env.example .env && nano .env

# everything set up in one command
make install

# verify
make test
```

The Docker image pins **PHP 8.3** and **Composer 2.7**. To upgrade PHP, change the single `FROM` line in `Dockerfile`.