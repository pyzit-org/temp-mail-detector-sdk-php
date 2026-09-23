<div align="center">

```
██████╗ ██╗   ██╗███████╗██╗████████╗
██╔══██╗╚██╗ ██╔╝╚══███╔╝██║╚══██╔══╝
██████╔╝ ╚████╔╝   ███╔╝ ██║   ██║
██╔═══╝   ╚██╔╝   ███╔╝  ██║   ██║
██║        ██║   ███████╗██║   ██║
╚═╝        ╚═╝   ╚══════╝╚═╝   ╚═╝

████████╗███████╗███╗   ███╗██████╗ ███╗   ███╗ █████╗ ██╗██╗
╚══██╔══╝██╔════╝████╗ ████║██╔══██╗████╗ ████║██╔══██╗██║██║
   ██║   █████╗  ██╔████╔██║██████╔╝██╔████╔██║███████║██║██║
   ██║   ██╔══╝  ██║╚██╔╝██║██╔═══╝ ██║╚██╔╝██║██╔══██║██║██║
   ██║   ███████╗██║ ╚═╝ ██║██║     ██║ ╚═╝ ██║██║  ██║██║███████╗
   ╚═╝   ╚══════╝╚═╝     ╚═╝╚═╝     ╚═╝     ╚═╝╚═╝  ╚═╝╚═╝╚══════╝
```

**The official PHP SDK for the [Pyzit Disposable Email Detector API](https://temp-mail-detector.pyzit.com)**

Stop fake signups. Block throwaway addresses. Protect your platform.

[![Latest Stable Version](https://img.shields.io/packagist/v/pyzit/tempmail?color=1D9E75&labelColor=0f0f0f&style=flat-square)](https://packagist.org/packages/pyzit/tempmail)
[![PHP Version](https://img.shields.io/packagist/php-v/pyzit/tempmail?color=1D9E75&labelColor=0f0f0f&style=flat-square)](https://packagist.org/packages/pyzit/tempmail)
[![License](https://img.shields.io/packagist/l/pyzit/tempmail?color=1D9E75&labelColor=0f0f0f&style=flat-square)](LICENSE)
[![Type Safety](https://img.shields.io/badge/types-declared-1D9E75?labelColor=0f0f0f&style=flat-square)](src/)

</div>

---

## ◈ What is this?

`pyzit/tempmail` is the official PHP client for the **Pyzit Disposable Email API** — a service that detects throwaway, temporary, and fake email addresses in real time.

```
User submits email          SDK validates it              Your app decides
─────────────────           ─────────────────             ───────────────
user@mailnator.com  ──────► is_disposable: True  ──────►  ✗  Reject signup
hi@yourcompany.com  ──────► is_disposable: False ──────►  ✓  Allow signup
```

Works with **Laravel**, **Symfony**, **WordPress**, plain PHP scripts — simple Composer integration.

---

## ◈ Table of contents

- [Installation](#-installation)
- [Quick start](#-quick-start)
- [API tiers](#-api-tiers)
- [All methods](#-all-methods)
  - [check()](#checkresult--checkemail-string)
  - [detailed()](#detailedresult--detailedemail-string)
  - [bulk()](#bulkresult--bulkemails-array)
- [Response models](#-response-models)
- [Error handling](#-error-handling)
- [Framework integrations](#-framework-integrations)
- [Configuration](#-configuration)
- [Development](#-development)

---

## ◈ Installation

```bash
composer require pyzit/tempmail
```

**Requirements:** PHP 8.1 or higher.

---

## ◈ Quick start

```php
use Pyzit\TempMail\TempMailClient;

$client = new TempMailClient('YOUR_API_TOKEN');

$result = $client->check('user@example.com');

if ($result->isDisposable) {
    echo '❌ Disposable email — rejected';
} else {
    echo '✅ Looks clean — allowed';
}
```

Get your API token at [temp-mail-detector.pyzit.com](https://temp-mail-detector.pyzit.com).

> **Important:** API tokens now use the prefix `pyzit_live_...` (e.g., `pyzit_live_7b19e2f4a5c84d12903456789abcdef0`).

> **Tip:** Store your token in an environment variable, never hard-code it.
> ```php
> $client = new TempMailClient($_ENV['PYZIT_TOKEN']);
> ```

---

## ◈ API tiers & quota model

**New architecture:** Plans determine quota, not endpoint access. Every plan can use all endpoints.

```
┌─────────────────────────────────────────────────────────────────────┐
│                         QUOTA MODEL                                  │
├──────────────────┬─────────────┬──────────────────────────────────┤
│  Plan            │  Simple     │  Detailed                        │
│                  │  Quota/mo   │  Quota/mo                        │
├──────────────────┼─────────────┼──────────────────────────────────┤
│  Free Developer  │  1,000      │  20                              │
│  Pro             │  50,000     │  1,000                           │
│  Scale           │  500,000    │  25,000                          │
│  Enterprise      │  Custom     │  Custom                          │
└──────────────────┴─────────────┴──────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      BULK BATCH LIMITS                                │
├──────────────────┬─────────────┬──────────────────────────────────┤
│  Plan            │  Max Emails │  Notes                           │
├──────────────────┼─────────────┼──────────────────────────────────┤
│  Free Developer  │  10         │  Per request                     │
│  Pro             │  50         │  Per request                     │
│  Scale           │  100        │  Per request                     │
│  Enterprise      │  500+       │  Custom configured               │
└──────────────────┴─────────────┴──────────────────────────────────┘
```

**Important:** 
- **Unlimited API keys** across all plans
- **Token scopes** determine access: `tmd:basic` (check + bulk), `tmd:detailed` (detailed), `tmd:*` (all)
- Any user on any plan can create tokens with any scope

---

## ◈ All methods

### `CheckResult` ← `check(string $email)`

The fastest check. One email in, one decision out. Uses `simple_quota`.

```php
$result = $client->check('user@mailnator.com');

$result->email          // 'user@mailnator.com'
$result->isDisposable   // true
$result->status         // 'disposable'
$result->isClean()      // false  (convenience method, opposite of isDisposable)
```

**Response shape:**

```
CheckResult
├── email           string  — the email you submitted
├── isDisposable    bool    — true = block it
├── status          string  — 'clean' | 'disposable'
└── isClean()       bool    — shorthand for not isDisposable
```

---

### `DetailedResult` ← `detailed(string $email)`

Full forensic analysis. DNS records, SMTP probing, reputation scoring,
domain age, signal breakdown. Uses `detailed_quota`. Requires `tmd:detailed` scope.

```php
$result = $client->detailed('suspicious@new-domain.io');

$result->riskLevel       // 'high'
$result->recommendation   // 'reject'
$result->reputationScore  // 0.0
$result->shouldReject()   // true  (convenience method)

// DNS intelligence
$result->details->dnsIntelligence->hasMx      // false
$result->details->dnsIntelligence->hasSpf     // false

// Signal breakdown
$result->details->signals->negative  // ['no_mx_records', 'new_domain', ...]
$result->details->signals->positive  // []

// Domain stability
$result->details->stability->domainAgeDays  // 0
$result->details->stability->isNewDomain    // true
```

**Response shape:**

```
DetailedResult
├── email              string
├── domain             string
├── isDisposable       bool
├── status             string
├── reputationScore    float   — 0.0 (worst) to 1.0 (best)
├── riskLevel          string  — 'low' | 'medium' | 'high'
├── recommendation     string  — 'accept' | 'challenge' | 'reject'
├── shouldReject()     bool    — shorthand: recommendation == 'reject'
├── shouldChallenge()  bool    — shorthand: recommendation == 'challenge'
├── shouldAccept()     bool    — shorthand: recommendation == 'accept'
├── isHighRisk()       bool    — shorthand: riskLevel == 'high'
├── isTrustedDomain()  bool    — shorthand: isTrusted
└── details
    ├── reputation
    │   ├── reputationScore      float
    │   ├── isDisposable         bool
    │   ├── disposableConfidence float
    │   ├── riskLevel            string
    │   └── recommendation        string
    ├── signals
    │   ├── positive   array    — trust signals
    │   ├── negative   array    — risk signals
    │   └── neutral    array    — ambiguous signals
    ├── dnsIntelligence
    │   ├── hasMx       bool
    │   ├── mxRecords   array of MxRecord
    │   ├── hasARecord  bool
    │   ├── hasSpf      bool
    │   ├── hasDmarc    bool
    │   └── error       string|null
    └── stability
        ├── stabilityScore   float
        ├── domainAgeDays   int
        ├── isNewDomain     bool
        └── riskFactors     array
```

---

### `BulkResult` ← `bulk(array $emails)`

Validate multiple emails in a single API call. Uses `simple_quota`. Batch size depends on your plan (Free=10, Pro=50, Scale=100).

```php
$result = $client->bulk([
    'hi@pyzit.com',
    'cyz@temp-mail.org',
    'user@mailnator.com',
]);

$result->processed          // 3
$result->results            // ['hi@pyzit.com' => false, 'cyz@temp-mail.org' => true, ...]

$result->disposableEmails() // ['cyz@temp-mail.org', 'user@mailnator.com']
$result->cleanEmails()      // ['hi@pyzit.com']
```

**Response shape:**

```
BulkResult
├── processed          int              — number of emails processed
├── status             string           — 'clean' | 'disposable' | 'mixed'
├── disposableCount    int              — number of disposable emails
├── flaggedDomains     array            — unique disposable domains
├── results            array            — ['email' => isDisposable]
├── disposableEmails() array            — emails where isDisposable=true
├── cleanEmails()      array            — emails where isDisposable=false
├── isAllClean()       bool             — all emails are clean
├── isAllDisposable()  bool             — all emails are disposable
└── isMixed()          bool             — mixed clean/disposable
```

---

## ◈ Response models

All responses are fully typed with PHP 8.1+ type declarations.
You get autocomplete, IDE support, and type safety.

```
Pyzit\TempMail\Models\
│
├── CheckResult          ← client->check()
├── DetailedResult       ← client->detailed()
│   └── DetailedDetails
│       ├── ReputationDetail
│       ├── Signals
│       ├── DnsIntelligence
│       │   └── MxRecord
│       └── StabilityInfo
└── BulkResult           ← client->bulk()
```

---

## ◈ Error handling

All SDK errors inherit from `PyzitException`, so you can catch everything
at one level or be specific.

```
PyzitException                    ← catch-all for any SDK error
├── AuthenticationException       ← HTTP 401 — bad or missing token
├── InsufficientScopeError        ← HTTP 403 — token lacks required scope
│   └── getRequiredScope()       ← string, e.g. 'tmd:basic' or 'tmd:detailed'
├── QuotaExceededError            ← HTTP 402 — monthly quota exhausted
│   └── getQuotaType()            ← string, 'simple' or 'detailed'
├── BatchLimitExceededError       ← HTTP 400 — bulk request exceeds plan limit
│   ├── getMaxAllowed()           ← int, plan's batch size limit
│   └── getAttempted()            ← int, emails attempted
├── ValidationError               ← HTTP 400 — malformed email or request
│   └── getFieldErrors()         ← array, field-specific errors
├── RateLimitException            ← HTTP 429 — rate limit exceeded
│   └── getRetryAfter()           ← int, seconds to wait
├── ApiException                  ← HTTP 5xx or unexpected status
│   ├── getStatusCode()           ← int
│   └── getResponseBody()         ← string, raw body for debugging
└── TimeoutException              ← request took too long
```

**Recommended pattern — catch specific errors:**

```php
use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\{
    AuthenticationException,
    InsufficientScopeError,
    QuotaExceededError,
    BatchLimitExceededError,
    RateLimitException,
    ApiException,
    TimeoutException,
    PyzitException
};

$client = new TempMailClient('YOUR_TOKEN');

try {
    $result = $client->check('user@example.com');

} catch (AuthenticationException $e) {
    // token is wrong — fail fast, don't retry
    die('Check your PYZIT_TOKEN environment variable.');

} catch (InsufficientScopeError $e) {
    // token lacks required scope
    echo "Your API token lacks the '{$e->getRequiredScope()}' scope.";

} catch (QuotaExceededError $e) {
    // monthly quota exhausted
    echo "You've exceeded your monthly {$e->getQuotaType()} quota. Upgrade your plan.";

} catch (BatchLimitExceededError $e) {
    // bulk request exceeds plan limit
    echo "Your plan allows max {$e->getMaxAllowed()} emails per bulk request.";

} catch (RateLimitException $e) {
    // back off and retry
    sleep($e->getRetryAfter());
    $result = $client->check('user@example.com');

} catch (TimeoutException $e) {
    // API too slow — decide how to handle in your app
    echo 'Pyzit API timed out — allowing email through.';

} catch (ApiException $e) {
    // unexpected server error
    echo "Unexpected error {$e->getStatusCode()}: {$e->getResponseBody()}";

} catch (PyzitException $e) {
    // fallback for any other SDK error
    echo 'Something went wrong with the Pyzit SDK.';
}
```

**Minimal pattern — just block disposables, ignore errors gracefully:**

```php
function isAllowed(string $email): bool {
    try {
        return !$client->check($email)->isDisposable;
    } catch (PyzitException $e) {
        return true; // fail open — let the email through if API is down
    }
}
```

---

## ◈ Framework integrations

### Laravel

```php
// app/Services/EmailValidator.php
namespace App\Services;

use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\PyzitException;

class EmailValidator
{
    private TempMailClient $client;

    public function __construct()
    {
        $this->client = new TempMailClient(env('PYZIT_TOKEN'));
    }

    public function validate(string $email): bool
    {
        try {
            $result = $this->client->check($email);
            return !$result->isDisposable;
        } catch (PyzitException $e) {
            return true; // fail open
        }
    }
}

// app/Http/Controllers/AuthController.php
public function register(Request $request, EmailValidator $validator)
{
    if (!$validator->validate($request->email)) {
        return response()->json([
            'error' => 'Disposable email addresses are not allowed.'
        ], 422);
    }
    
    // continue with registration...
}
```

---

### Symfony

```php
// src/Validator/NotDisposableEmail.php
namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\PyzitException;

class NotDisposableEmailValidator extends ConstraintValidator
{
    private TempMailClient $client;

    public function __construct()
    {
        $this->client = new TempMailClient($_ENV['PYZIT_TOKEN']);
    }

    public function validate($value, Constraint $constraint): void
    {
        try {
            $result = $this->client->check((string)$value);
            if ($result->isDisposable) {
                $this->context->buildViolation('Disposable emails are not allowed.')
                    ->addViolation();
            }
        } catch (PyzitException $e) {
            // fail open if API is unreachable
        }
    }
}

// src/Validator/NotDisposableEmail.php (constraint class)
namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NotDisposableEmail extends Constraint
{
    public string $message = 'Disposable email addresses are not allowed.';
}
```

---

### WordPress

```php
// functions.php
require_once __DIR__ . '/vendor/autoload.php';

use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\PyzitException;

function validate_disposable_email($email) {
    $client = new TempMailClient(getenv('PYZIT_TOKEN'));
    
    try {
        $result = $client->check($email);
        return !$result->isDisposable;
    } catch (PyzitException $e) {
        return true; // fail open
    }
}

add_filter('registration_errors', function($errors, $sanitized_user_login, $user_email) {
    if (!validate_disposable_email($user_email)) {
        $errors->add('disposable_email', 'Disposable email addresses are not allowed.');
    }
    return $errors;
}, 10, 3);
```

---

## ◈ Configuration

`TempMailClient` accepts the following options:

```php
$client = new TempMailClient(
    apiKey: 'YOUR_TOKEN',          // required
    baseUrl: 'https://api.pyzit.com',  // override for testing
    timeout: 5,                     // seconds, default 5
    maxRetries: 2,                  // retry attempts for 429/5xx, default 2
    failOpen: false,                // return clean on network errors, default false
);
```

| Parameter   | Type    | Default                                      | Description                        |
|-------------|---------|----------------------------------------------|------------------------------------|
| `apiKey`    | `string`| —                                            | Your Pyzit API token (required)    |
| `baseUrl`   | `string`| `https://api.pyzit.com`                      | Override for local testing / mocks |
| `timeout`   | `int`   | `5`                                          | Request timeout in seconds         |
| `maxRetries`| `int`   | `2`                                          | Retry attempts for 429/5xx errors  |
| `failOpen`  | `bool`  | `false`                                      | Return clean result on network errors|

---

## ◈ Development

This project uses standard PHP tooling.

```bash
# clone
git clone https://github.com/pyzit-org/temp-mail-detector-sdk-php.git
cd temp-mail-detector-sdk-php

# install dependencies
composer install

# run tests (no real API calls — all HTTP is mocked)
vendor/bin/phpunit

# tests with Docker (recommended for local development)
docker compose up
docker compose run --rm sdk vendor/bin/phpunit

# code style check
vendor/bin/phpcs --standard=PSR12 src/
```

**Project layout:**

```
src/
├── Exceptions/
│   ├── PyzitException.php         ← base exception class
│   ├── AuthenticationException.php
│   ├── InsufficientScopeError.php
│   ├── QuotaExceededError.php
│   ├── BatchLimitExceededError.php
│   ├── ValidationError.php
│   ├── RateLimitException.php
│   ├── ApiException.php
│   ├── TimeoutException.php
│   ├── ScopeException.php
│   └── PlanRequiredException.php
├── Models/
│   ├── CheckResult.php
│   ├── DetailedResult.php
│   ├── DetailedDetails.php
│   ├── ReputationDetail.php
│   ├── Signals.php
│   ├── DnsIntelligence.php
│   ├── MxRecord.php
│   ├── StabilityInfo.php
│   └── BulkResult.php
├── HttpClient.php                 ← internal HTTP layer
└── TempMailClient.php             ← public API surface

tests/
├── ExceptionsTest.php
├── HttpClientErrorTest.php
├── ModelsTest.php
├── TempMailClientTest.php
└── Fixtures.php                   ← shared test data
```

---

## ◈ Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## ◈ License

[MIT](LICENSE) © [Pyzit](https://pyzit.com)

---

<div align="center">

```
Built with care by the Pyzit team · https://pyzit.com
```

</div>
