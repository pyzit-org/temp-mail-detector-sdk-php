<?php

declare(strict_types=1);

/**
 * pyzit/tempmail PHP SDK — comprehensive usage examples
 *
 * Run:
 *   php example_usage.php
 *
 * Requires PYZIT_TOKEN env var:
 *   export PYZIT_TOKEN="your_real_token"   # Mac/Linux
 *   set PYZIT_TOKEN=your_real_token        # Windows
 */

require __DIR__ . '/vendor/autoload.php';

use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\InsufficientScopeError;
use Pyzit\TempMail\Exceptions\ScopeException;
use Pyzit\TempMail\Exceptions\PlanRequiredException;
use Pyzit\TempMail\Exceptions\QuotaExceededError;
use Pyzit\TempMail\Exceptions\BatchLimitExceededError;
use Pyzit\TempMail\Exceptions\ValidationError;
use Pyzit\TempMail\Exceptions\RateLimitException;
use Pyzit\TempMail\Exceptions\ApiException;
use Pyzit\TempMail\Exceptions\TimeoutException;
use Pyzit\TempMail\Exceptions\PyzitException;

// ── bootstrap ─────────────────────────────────────────────────────

$token = getenv('PYZIT_TOKEN');
if (!$token) {
    fwrite(STDERR, "ERROR: set PYZIT_TOKEN environment variable first.\n");
    exit(1);
}

// Basic client initialization
$client = new TempMailClient($token);

// Advanced client with all configuration options
$clientAdvanced = new TempMailClient(
    apiKey: $token,
    baseUrl: 'https://api.pyzit.com',
    timeout: 10,
    maxRetries: 3,
    failOpen: true
);

$SEP  = str_repeat('─', 60);
$PASS = '✓';
$FAIL = '✗';
$INFO = '→';

function printHeader(string $t): void { echo "\n" . $GLOBALS['SEP'] . "\n  $t\n" . $GLOBALS['SEP'] . "\n"; }
function printOk(string $m): void     { echo "  {$GLOBALS['PASS']}  $m\n"; }
function printFail(string $m): void   { echo "  {$GLOBALS['FAIL']}  $m\n"; }
function printInfo(string $m): void   { echo "  {$GLOBALS['INFO']}  $m\n"; }


// ── 1. BASIC CHECK ────────────────────────────────────────────────

printHeader('1. check() — basic email validation');

$emails = [
    'hi@pyzit.com',
    'user@mailnator.com',
    'test@guerrillamail.com',
    'support@github.com',
    'fake@temp-mail.org',
];

foreach ($emails as $email) {
    $r   = $client->check($email);
    $tag = $r->isDisposable ? 'DISPOSABLE' : 'CLEAN     ';
    echo "  [$tag]  " . str_pad($email, 35) . "  status=" . strtoupper($r->status) . "\n";
}

echo "\n";
printInfo('CheckResult properties:');
$r = $client->check('hi@pyzit.com');
echo "    \$r->email        = \"{$r->email}\"\n";
echo "    \$r->isDisposable = " . ($r->isDisposable ? 'true' : 'false') . "\n";
echo "    \$r->status       = \"{$r->status}\"\n";
echo "    \$r->isClean()    = " . ($r->isClean() ? 'true' : 'false') . "\n";


// ── 2. DETAILED ANALYSIS ──────────────────────────────────────────

printHeader('2. detailed() — comprehensive email analysis');

$cases = [
    ['hi@pyzit.com',       'trusted domain'],
    ['user@mailnator.com', 'known disposable'],
];

foreach ($cases as [$email, $label]) {
    echo "\n  [$label]  $email\n";
    try {
        $r = $client->detailed($email);
        
        // Top-level properties
        echo "    email              : \"{$r->email}\"\n";
        echo "    domain             : \"{$r->domain}\"\n";
        echo "    isDisposable       : " . ($r->isDisposable ? 'true' : 'false') . "\n";
        echo "    isTrusted          : " . ($r->isTrusted ? 'true' : 'false') . "\n";
        echo "    classification     : \"{$r->classification}\"\n";
        echo "    reputationScore    : " . number_format($r->reputationScore, 2) . "\n";
        echo "    riskLevel          : \"{$r->riskLevel}\"\n";
        echo "    recommendation     : \"{$r->recommendation}\"\n";
        echo "    isValidFormat      : " . ($r->isValidFormat ? 'true' : 'false') . "\n";
        echo "    ruleApplied        : " . ($r->ruleApplied ?? 'null') . "\n";
        echo "    status             : \"{$r->status}\"\n";
        
        // Helper methods
        echo "    Helper methods:\n";
        echo "      shouldReject()   : " . ($r->shouldReject() ? 'true' : 'false') . "\n";
        echo "      shouldChallenge(): " . ($r->shouldChallenge() ? 'true' : 'false') . "\n";
        echo "      shouldAccept()  : " . ($r->shouldAccept() ? 'true' : 'false') . "\n";
        echo "      isHighRisk()     : " . ($r->isHighRisk() ? 'true' : 'false') . "\n";
        echo "      isTrustedDomain(): " . ($r->isTrustedDomain() ? 'true' : 'false') . "\n";
        echo "      isClean()        : " . ($r->isClean() ? 'true' : 'false') . "\n";

        // Nested: Reputation
        echo "    Reputation:\n";
        $rep = $r->details->reputation;
        echo "      reputationScore      : " . number_format($rep->reputationScore, 2) . "\n";
        echo "      isDisposable         : " . ($rep->isDisposable ? 'true' : 'false') . "\n";
        echo "      disposableConfidence : " . number_format($rep->disposableConfidence, 2) . "\n";
        echo "      riskLevel            : \"{$rep->riskLevel}\"\n";
        echo "      recommendation        : \"{$rep->recommendation}\"\n";

        // Nested: Signals
        echo "    Signals:\n";
        $sig = $r->details->signals;
        echo "      positive : " . (implode(', ', $sig->positive) ?: '(none)') . "\n";
        echo "      negative : " . (implode(', ', $sig->negative) ?: '(none)') . "\n";
        echo "      neutral  : " . (implode(', ', $sig->neutral)  ?: '(none)') . "\n";

        // Nested: DNS Intelligence
        echo "    DNS Intelligence:\n";
        $dns = $r->details->dnsIntelligence;
        echo "      hasMx       : " . ($dns->hasMx ? 'true' : 'false') . "\n";
        echo "      hasARecord  : " . ($dns->hasARecord ? 'true' : 'false') . "\n";
        echo "      hasSpf      : " . ($dns->hasSpf ? 'true' : 'false') . "\n";
        echo "      hasDmarc    : " . ($dns->hasDmarc ? 'true' : 'false') . "\n";
        echo "      error       : " . ($dns->error ?? 'null') . "\n";

        if (count($dns->mxRecords) > 0) {
            echo "      mxRecords:\n";
            foreach ($dns->mxRecords as $mx) {
                echo "        priority={$mx->priority}  exchange={$mx->exchange}  ips=" . implode(', ', $mx->ips) . "\n";
            }
        } else {
            echo "      mxRecords: (none)\n";
        }

        // Nested: Stability
        echo "    Stability:\n";
        $stab = $r->details->stability;
        echo "      stabilityScore : " . number_format($stab->stabilityScore, 2) . "\n";
        echo "      domainAgeDays  : {$stab->domainAgeDays}\n";
        echo "      isNewDomain    : " . ($stab->isNewDomain ? 'true' : 'false') . "\n";
        echo "      riskFactors    : " . (implode(', ', $stab->riskFactors) ?: '(none)') . "\n";

    } catch (PlanRequiredException $e) {
        printFail("Need '{$e->getRequiredPlan()}' plan for detailed()");
    } catch (ScopeException $e) {
        printFail("Scope missing: {$e->getRequiredScope()}");
    } catch (InsufficientScopeError $e) {
        printFail("Insufficient scope: {$e->getRequiredScope()}");
    }
}


// ── 3. BULK VALIDATION ────────────────────────────────────────────

printHeader('3. bulk() — batch email validation');

$emails = [
    'hi@pyzit.com',
    'user@mailnator.com',
    'support@github.com',
    'test@guerrillamail.com',
    'hello@microsoft.com',
    'fake@temp-mail.org',
];

printInfo('Sending ' . count($emails) . " emails in one API call...\n");

try {
    $r = $client->bulk($emails);
    
    // BulkResult properties
    echo "  processed         : {$r->processed}\n";
    echo "  status            : \"{$r->status}\"\n";
    echo "  disposableCount   : {$r->disposableCount}\n";
    echo "  flaggedDomains    : " . implode(', ', $r->flaggedDomains) . "\n";
    
    // Helper methods
    echo "  Helper methods:\n";
    echo "    isAllClean()      : " . ($r->isAllClean() ? 'true' : 'false') . "\n";
    echo "    isAllDisposable() : " . ($r->isAllDisposable() ? 'true' : 'false') . "\n";
    echo "    isMixed()         : " . ($r->isMixed() ? 'true' : 'false') . "\n";

    $disposable = $r->disposableEmails();
    $clean      = $r->cleanEmails();

    echo "\n  Disposable (" . count($disposable) . "):\n";
    foreach ($disposable as $e) echo "    $FAIL  $e\n";

    echo "\n  Clean (" . count($clean) . "):\n";
    foreach ($clean as $e) echo "    $PASS  $e\n";

} catch (PlanRequiredException $e) {
    printFail("Need '{$e->getRequiredPlan()}' plan for bulk()");
} catch (BatchLimitExceededError $e) {
    printFail("Batch limit exceeded: max {$e->getMaxAllowed()}, attempted {$e->getAttempted()}");
}


// ── 4. PRODUCTION SIGNUP GUARD ────────────────────────────────────

printHeader('4. Production signup guard — fail open');

function validateSignup(TempMailClient $client, string $email): array
{
    try {
        $r = $client->check($email);
        return [
            'email'   => $email,
            'allowed' => !$r->isDisposable,
            'reason'  => $r->isDisposable ? 'disposable_email' : null,
            'status'  => $r->status,
            'error'   => null,
        ];
    } catch (AuthenticationException) {
        $error = 'auth_error';
    } catch (InsufficientScopeError $e) {
        $error = 'missing_scope:' . $e->getRequiredScope();
    } catch (QuotaExceededError $e) {
        $error = 'quota_exceeded:' . $e->getQuotaType();
    } catch (RateLimitException $e) {
        $error = 'rate_limit:' . $e->getRetryAfter() . 's';
    } catch (TimeoutException) {
        $error = 'timeout';
    } catch (PyzitException) {
        $error = 'sdk_error';
    }
    // Fail open — never block a real user because the API is down
    return ['email' => $email, 'allowed' => true, 'reason' => null, 'status' => 'unknown', 'error' => $error];
}

$emails = ['hi@pyzit.com', 'user@mailnator.com', 'support@github.com', 'fake@temp-mail.org'];

printf("\n  %-35s %-10s %-20s %s\n", 'EMAIL', 'ALLOWED', 'REASON', 'STATUS');
echo "  " . str_repeat('─', 35) . " " . str_repeat('─', 10) . " " . str_repeat('─', 20) . " " . str_repeat('─', 10) . "\n";

foreach ($emails as $email) {
    $res     = validateSignup($client, $email);
    $allowed = $res['allowed'] ? "$PASS yes" : "$FAIL no ";
    $reason  = $res['reason'] ?? '';
    $errNote = $res['error']  ? "  [err: {$res['error']}]" : '';
    printf("  %-35s %-10s %-20s %s%s\n", $email, $allowed, $reason, $res['status'], $errNote);
}


// ── 5. COMPREHENSIVE ERROR HANDLING ──────────────────────────────

printHeader('5. Error handling — every exception path');

printInfo("Bad token → AuthenticationException:");
try {
    $bad = new TempMailClient('invalid-token');
    $bad->check('hi@pyzit.com');
    printFail('Should have thrown!');
} catch (AuthenticationException $e) {
    printOk("AuthenticationException caught: \"{$e->getMessage()}\"");
} catch (PyzitException $e) {
    printOk(get_class($e) . " caught: \"{$e->getMessage()}\"");
}

echo "\n";
printInfo("Exception attributes and methods:");
$exceptions = [
    new PlanRequiredException('business'),
    new ScopeException('detailed:tempemail_check'),
    new InsufficientScopeError('missing scope', 'tmd:detailed'),
    new QuotaExceededError('simple', 'Simple quota exceeded'),
    new BatchLimitExceededError(10, 15, 'Too many emails'),
    new ValidationError(['email' => 'Invalid format'], 'Validation failed'),
    new RateLimitException(42),
    new ApiException(503, 'Service Unavailable', '{"error": "maintenance"}'),
];
foreach ($exceptions as $e) {
    $attr = match (true) {
        $e instanceof PlanRequiredException => ['requiredPlan'  => $e->getRequiredPlan()],
        $e instanceof ScopeException        => ['requiredScope' => $e->getRequiredScope()],
        $e instanceof InsufficientScopeError => ['requiredScope' => $e->getRequiredScope()],
        $e instanceof QuotaExceededError    => ['quotaType'     => $e->getQuotaType()],
        $e instanceof BatchLimitExceededError => ['maxAllowed' => $e->getMaxAllowed(), 'attempted' => $e->getAttempted()],
        $e instanceof ValidationError       => ['fieldErrors'   => $e->getFieldErrors()],
        $e instanceof RateLimitException    => ['retryAfter'    => $e->getRetryAfter()],
        $e instanceof ApiException          => ['statusCode'    => $e->getStatusCode(), 'body' => substr($e->getResponseBody(), 0, 50)],
        default                             => [],
    };
    printOk(str_pad(get_class($e), 45) . json_encode($attr));
}


// ── 6. FRAMEWORK PATTERNS ─────────────────────────────────────────

printHeader('6. Framework patterns — copy-paste ready');
echo <<<'CODE'

  ── Laravel middleware ────────────────────────────────────────
  // app/Http/Middleware/BlockDisposableEmails.php
  use Pyzit\TempMail\TempMailClient;
  use Pyzit\TempMail\Exceptions\PyzitException;

  class BlockDisposableEmails
  {
      public function __construct(private TempMailClient $client) {}

      public function handle(Request $request, Closure $next): Response
      {
          try {
              $r = $this->client->check($request->input('email'));
              if ($r->isDisposable) {
                  return response()->json(['error' => 'Disposable emails not allowed.'], 422);
              }
          } catch (PyzitException) {
              // fail open — API error should never block a real user
          }
          return $next($request);
      }
  }

  ── Laravel service provider with dependency injection ───────
  // app/Providers/TempMailServiceProvider.php
  use Pyzit\TempMail\TempMailClient;
  use Illuminate\Support\ServiceProvider;

  class TempMailServiceProvider extends ServiceProvider
  {
      public function register(): void
      {
          $this->app->singleton(TempMailClient::class, function () {
              return new TempMailClient(
                  apiKey: config('services.pyzit.token'),
                  timeout: config('services.pyzit.timeout', 5),
                  failOpen: config('services.pyzit.fail_open', true)
              );
          });
      }
  }

  ── Symfony validator constraint ──────────────────────────────
  // src/Validator/NotDisposableEmail.php
  use Pyzit\TempMail\TempMailClient;
  use Pyzit\TempMail\Exceptions\PyzitException;
  use Symfony\Component\Validator\Constraint;
  use Symfony\Component\Validator\ConstraintValidator;

  #[\Attribute]
  class NotDisposableEmail extends Constraint
  {
      public string $message = 'Disposable emails are not allowed.';
  }

  class NotDisposableEmailValidator extends ConstraintValidator
  {
      public function __construct(private TempMailClient $client) {}

      public function validate($value, Constraint $constraint): void
      {
          try {
              $r = $this->client->check((string)$value);
              if ($r->isDisposable) {
                  $this->context->buildViolation($constraint->message)->addViolation();
              }
          } catch (PyzitException) { /* fail open */ }
      }
  }

  ── WordPress plugin integration ───────────────────────────────
  // functions.php
  use Pyzit\TempMail\TempMailClient;
  use Pyzit\TempMail\Exceptions\PyzitException;

  function pyzit_validate_email($email) {
      $client = new TempMailClient(getenv('PYZIT_TOKEN'));
      
      try {
          $result = $client->check($email);
          return !$result->isDisposable;
      } catch (PyzitException $e) {
          error_log('Pyzit SDK error: ' . $e->getMessage());
          return true; // fail open
      }
  }

  add_filter('registration_errors', function($errors, $sanitized_user_login, $user_email) {
      if (!pyzit_validate_email($user_email)) {
          $errors->add('disposable_email', 'Disposable email addresses are not allowed.');
      }
      return $errors;
  }, 10, 3);

  ── Plain PHP with comprehensive error handling ──────────────
  use Pyzit\TempMail\TempMailClient;
  use Pyzit\TempMail\Exceptions\{
      AuthenticationException,
      InsufficientScopeError,
      QuotaExceededError,
      RateLimitException,
      PyzitException
  };

  $client = new TempMailClient($_ENV['PYZIT_TOKEN']);

  function validateEmail(string $email): bool {
      try {
          $result = $client->check($email);
          return !$result->isDisposable;
      } catch (AuthenticationException $e) {
          error_log('Invalid API token: ' . $e->getMessage());
          return true; // fail open
      } catch (InsufficientScopeError $e) {
          error_log('Missing scope: ' . $e->getRequiredScope());
          return true; // fail open
      } catch (QuotaExceededError $e) {
          error_log('Quota exceeded: ' . $e->getQuotaType());
          return true; // fail open
      } catch (RateLimitException $e) {
          sleep($e->getRetryAfter());
          return validateEmail($email); // retry
      } catch (PyzitException $e) {
          error_log('SDK error: ' . $e->getMessage());
          return true; // fail open
      }
  }

  if (!validateEmail($_POST['email'] ?? '')) {
      http_response_code(422);
      echo json_encode(['error' => 'Disposable emails are not allowed.']);
      exit;
  }

CODE;


// ── 7. CLIENT CONFIGURATION OPTIONS ─────────────────────────────

printHeader('7. Client configuration options');

printInfo('Advanced client configuration:');
echo "  \$client = new TempMailClient(\n";
echo "      apiKey: 'your_token',\n";
echo "      baseUrl: 'https://api.pyzit.com',  // Override for testing\n";
echo "      timeout: 10,                      // Request timeout in seconds\n";
echo "      maxRetries: 3,                    // Retry attempts for 429/5xx\n";
echo "      failOpen: true                    // Return clean on network errors\n";
echo "  );\n";

printInfo('Fail-open behavior (recommended for production):');
$failOpenClient = new TempMailClient($token, failOpen: true);
echo "  Client with failOpen=true will return clean results on network errors\n";
echo "  This ensures users are never blocked due to API downtime\n";


// ── 8. RESPONSE MODEL EXPLORATION ───────────────────────────────

printHeader('8. Response model exploration');

printInfo('CheckResult model structure:');
$checkResult = $client->check('test@example.com');
echo "  Properties:\n";
echo "    email          : \"{$checkResult->email}\"\n";
echo "    isDisposable   : " . ($checkResult->isDisposable ? 'true' : 'false') . "\n";
echo "    status         : \"{$checkResult->status}\"\n";
echo "  Methods:\n";
echo "    isClean()      : " . ($checkResult->isClean() ? 'true' : 'false') . "\n";

printInfo('DetailedResult model structure:');
try {
    $detailedResult = $client->detailed('test@example.com');
    echo "  Top-level properties:\n";
    echo "    email, domain, isDisposable, isTrusted, classification\n";
    echo "    reputationScore, riskLevel, recommendation, isValidFormat\n";
    echo "    ruleApplied, status\n";
    echo "  Helper methods:\n";
    echo "    shouldReject(), shouldChallenge(), shouldAccept()\n";
    echo "    isHighRisk(), isTrustedDomain(), isClean()\n";
    echo "  Nested objects:\n";
    echo "    details->reputation (ReputationDetail)\n";
    echo "    details->signals (Signals)\n";
    echo "    details->dnsIntelligence (DnsIntelligence)\n";
    echo "    details->stability (StabilityInfo)\n";
} catch (PyzitException $e) {
    printInfo("Skipping detailed example due to: " . $e->getMessage());
}

printInfo('BulkResult model structure:');
$bulkResult = $client->bulk(['test@example.com']);
echo "  Properties:\n";
echo "    processed, status, disposableCount, flaggedDomains\n";
echo "    results (array: email => isDisposable)\n";
echo "  Helper methods:\n";
echo "    disposableEmails(), cleanEmails()\n";
echo "    isAllClean(), isAllDisposable(), isMixed()\n";


// ── 9. ADVANCED USAGE PATTERNS ─────────────────────────────────

printHeader('9. Advanced usage patterns');

printInfo('Custom retry logic implementation:');
echo "  function checkWithRetry(TempMailClient \$client, string \$email, int \$maxRetries = 3): ?CheckResult {\n";
echo "      for (\$i = 0; \$i < \$maxRetries; \$i++) {\n";
echo "          try {\n";
echo "              return \$client->check(\$email);\n";
echo "          } catch (RateLimitException \$e) {\n";
echo "              if (\$i < \$maxRetries - 1) {\n";
echo "                  sleep(\$e->getRetryAfter());\n";
echo "              } else {\n";
echo "                  throw \$e;\n";
echo "              }\n";
echo "          }\n";
echo "      }\n";
echo "      return null;\n";
echo "  }\n";

printInfo('Bulk processing with progress tracking:');
echo "  function processBulkWithProgress(TempMailClient \$client, array \$emails): array {\n";
echo "      \$results = [];\n";
echo "      \$batchSize = 10; // Process in batches of 10\n";
echo "      \n";
echo "      foreach (array_chunk(\$emails, \$batchSize) as \$batch) {\n";
echo "          try {\n";
echo "              \$batchResult = \$client->bulk(\$batch);\n";
echo "              \$results = array_merge(\$results, \$batchResult->results);\n";
echo "              echo \"Processed \" . \$batchResult->processed . \" emails\\n\";\n";
echo "          } catch (BatchLimitExceededError \$e) {\n";
echo "              echo \"Batch too large. Max: {\$e->getMaxAllowed()}\\n\";\n";
echo "              break;\n";
echo "          }\n";
echo "      }\n";
echo "      \n";
echo "      return \$results;\n";
echo "  }\n";


// ── 10. ENVIRONMENT SETUP BEST PRACTICES ─────────────────────────

printHeader('10. Environment setup best practices');

printInfo('Environment variable configuration:');
echo "  // .env file\n";
echo "  PYZIT_TOKEN=pyzit_live_your_token_here\n";
echo "  PYZIT_BASE_URL=https://api.pyzit.com\n";
echo "  PYZIT_TIMEOUT=10\n";
echo "  PYZIT_FAIL_OPEN=true\n";

printInfo('Loading configuration:');
echo "  \$token = \$_ENV['PYZIT_TOKEN'] ?? throw new RuntimeException('PYZIT_TOKEN not set');\n";
echo "  \$client = new TempMailClient(\n";
echo "      apiKey: \$token,\n";
echo "      baseUrl: \$_ENV['PYZIT_BASE_URL'] ?? 'https://api.pyzit.com',\n";
echo "      timeout: (int)(\$_ENV['PYZIT_TIMEOUT'] ?? 5),\n";
echo "      failOpen: filter_var(\$_ENV['PYZIT_FAIL_OPEN'] ?? 'false', FILTER_VALIDATE_BOOLEAN)\n";
echo "  );\n";


// ── 11. TESTING AND DEVELOPMENT ─────────────────────────────────

printHeader('11. Testing and development');

printInfo('Testing with local API:');
echo "  // For local development, override the base URL\n";
echo "  \$localClient = new TempMailClient(\n";
echo "      apiKey: 'test-token',\n";
echo "      baseUrl: 'http://localhost:8000'  // Local development server\n";
echo "  );\n";

printInfo('Mocking for unit tests:');
echo "  // Create mock responses for testing\n";
echo "  \$mockCheckResult = Pyzit\\TempMail\\Models\\CheckResult::fromArray([\n";
echo "      'email' => 'test@example.com',\n";
echo "      'is_disposable' => false,\n";
echo "      'status' => 'clean'\n";
echo "  ]);\n";


// ── 12. PERFORMANCE OPTIMIZATION ───────────────────────────────

printHeader('12. Performance optimization');

printInfo('Connection pooling (advanced):');
echo "  // Reuse client instance for multiple requests\n";
echo "  \$client = new TempMailClient(\$token, maxRetries: 3);\n";
echo "  // Use same client for multiple calls to reuse connection\n";
echo "  foreach (\$emails as \$email) {\n";
echo "      \$client->check(\$email);\n";
echo "  }\n";


// ── 13. MONITORING AND LOGGING ─────────────────────────────────

printHeader('13. Monitoring and logging');

printInfo('Structured logging:');
echo "  function logApiCall(string \$method, string \$email, ?Exception \$error = null): void {\n";
echo "      \$logData = [\n";
echo "          'timestamp' => date('c'),\n";
echo "          'method' => \$method,\n";
echo "          'email' => \$email,\n";
echo "          'success' => \$error === null,\n";
echo "      ];\n";
echo "      \n";
echo "      if (\$error) {\n";
echo "          \$logData['error'] = [\n";
echo "              'type' => get_class(\$error),\n";
echo "              'message' => \$error->getMessage(),\n";
echo "          ];\n";
echo "      }\n";
echo "      \n";
echo "      error_log(json_encode(\$logData));\n";
echo "  }\n";


// ── 14. SECURITY BEST PRACTICES ───────────────────────────────

printHeader('14. Security best practices');

printInfo('API key security:');
echo "  // Never hardcode API keys\n";
echo "  // Always use environment variables\n";
echo "  // Rotate keys regularly\n";
echo "  // Use different keys for different environments\n";

printInfo('Input validation:');
echo "  // Always validate email format before API call\n";
echo "  function validateAndCheck(TempMailClient \$client, string \$email): ?CheckResult {\n";
echo "      if (!filter_var(\$email, FILTER_VALIDATE_EMAIL)) {\n";
echo "          throw new InvalidArgumentException('Invalid email format');\n";
echo "      }\n";
echo "      \n";
echo "      return \$client->check(\$email);\n";
echo "  }\n";


// ── DONE ─────────────────────────────────────────────────────────

echo "\n" . str_repeat('═', 60) . "\n";
echo "  All examples complete.\n";
echo "  Covered: All SDK features, error handling, framework patterns\n";
echo str_repeat('═', 60) . "\n\n";