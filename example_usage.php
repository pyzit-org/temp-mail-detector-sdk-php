<?php

declare(strict_types=1);

/**
 * pyzit/tempmail PHP SDK — real-world usage examples
 *
 * Run:
 *   php example_usage.php
 *
 * Requires PYZIT_TOKEN env var:
 *   export PYZIT_TOKEN="your_real_token"   # Mac/Linux
 */

require __DIR__ . '/vendor/autoload.php';

use Pyzit\TempMail\TempMailClient;
use Pyzit\TempMail\Exceptions\AuthenticationException;
use Pyzit\TempMail\Exceptions\ScopeException;
use Pyzit\TempMail\Exceptions\PlanRequiredException;
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

$client = new TempMailClient($token);
// with all options:
// $client = new TempMailClient($token, timeout: 15, baseUrl: 'https://api-tempmail.pyzit.com/v1');

$SEP  = str_repeat('─', 60);
$PASS = '✓';
$FAIL = '✗';
$INFO = '→';

function printHeader(string $t): void { echo "\n" . $GLOBALS['SEP'] . "\n  $t\n" . $GLOBALS['SEP'] . "\n"; }
function printOk(string $m): void     { echo "  {$GLOBALS['PASS']}  $m\n"; }
function printFail(string $m): void   { echo "  {$GLOBALS['FAIL']}  $m\n"; }
function printInfo(string $m): void   { echo "  {$GLOBALS['INFO']}  $m\n"; }


// ── 1. BASIC CHECK ────────────────────────────────────────────────

printHeader('1. check() — free tier');

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
printInfo('Result fields:');
$r = $client->check('hi@pyzit.com');
echo "    \$r->email        = \"{$r->email}\"\n";
echo "    \$r->isDisposable = " . ($r->isDisposable ? 'true' : 'false') . "\n";
echo "    \$r->status       = \"{$r->status}\"\n";
echo "    \$r->isClean()    = " . ($r->isClean() ? 'true' : 'false') . "\n";


// ── 2. DETAILED ANALYSIS ──────────────────────────────────────────

printHeader('2. detailed() — Pro tier');

$cases = [
    ['hi@pyzit.com',       'trusted domain'],
    ['user@mailnator.com', 'known disposable'],
];

foreach ($cases as [$email, $label]) {
    echo "\n  [$label]  $email\n";
    try {
        $r = $client->detailed($email);
        echo "    reputation_score : " . number_format($r->reputationScore, 2) . "\n";
        echo "    risk_level       : " . strtoupper($r->riskLevel) . "\n";
        echo "    recommendation   : " . strtoupper($r->recommendation) . "\n";
        echo "    shouldReject()   : " . ($r->shouldReject() ? 'true' : 'false') . "\n";

        $dns = $r->details->dnsIntelligence;
        echo "    dns.has_mx       : " . ($dns->hasMx ? 'true' : 'false') . "\n";
        echo "    dns.has_spf      : " . ($dns->hasSpf ? 'true' : 'false') . "\n";
        echo "    dns.has_dmarc    : " . ($dns->hasDmarc ? 'true' : 'false') . "\n";

        if (count($dns->mxRecords) > 0) {
            echo "    dns.mx_records   :\n";
            foreach ($dns->mxRecords as $mx) {
                echo "      priority={$mx->priority}  exchange={$mx->exchange}  ips=" . implode(', ', $mx->ips) . "\n";
            }
        } else {
            echo "    dns.mx_records   : (none)\n";
        }

        $sig = $r->details->signals;
        echo "    signals.positive : " . (implode(', ', $sig->positive) ?: '(none)') . "\n";
        echo "    signals.negative : " . (implode(', ', $sig->negative) ?: '(none)') . "\n";
        echo "    signals.neutral  : " . (implode(', ', $sig->neutral)  ?: '(none)') . "\n";

        $stab = $r->details->stability;
        echo "    stability.score  : " . number_format($stab->stabilityScore, 2) . "\n";
        echo "    domain_age_days  : {$stab->domainAgeDays}\n";
        echo "    is_new_domain    : " . ($stab->isNewDomain ? 'true' : 'false') . "\n";

    } catch (PlanRequiredException $e) {
        printFail("Need '{$e->getRequiredPlan()}' plan for detailed()");
    } catch (ScopeException $e) {
        printFail("Scope missing: {$e->getRequiredScope()}");
    }
}


// ── 3. BULK ───────────────────────────────────────────────────────

printHeader('3. bulk() — Business tier');

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
    echo "  processed: {$r->processed}\n";

    $disposable = $r->disposableEmails();
    $clean      = $r->cleanEmails();

    echo "\n  Disposable (" . count($disposable) . "):\n";
    foreach ($disposable as $e) echo "    $FAIL  $e\n";

    echo "\n  Clean (" . count($clean) . "):\n";
    foreach ($clean as $e) echo "    $PASS  $e\n";

} catch (PlanRequiredException $e) {
    printFail("Need '{$e->getRequiredPlan()}' plan for bulk()");
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
    } catch (ScopeException $e) {
        $error = 'missing_scope:' . $e->getRequiredScope();
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
printInfo("Exception attributes:");
$exceptions = [
    new PlanRequiredException('business'),
    new ScopeException('detailed:tempemail_check'),
    new RateLimitException(42),
    new ApiException(503, 'Service Unavailable'),
];
foreach ($exceptions as $e) {
    $attr = match (true) {
        $e instanceof PlanRequiredException => ['requiredPlan'  => $e->getRequiredPlan()],
        $e instanceof ScopeException        => ['requiredScope' => $e->getRequiredScope()],
        $e instanceof RateLimitException    => ['retryAfter'    => $e->getRetryAfter()],
        $e instanceof ApiException          => ['statusCode'    => $e->getStatusCode()],
        default                             => [],
    };
    printOk(str_pad(get_class($e), 40) . json_encode($attr));
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

  ── Symfony validator constraint ──────────────────────────────
  // src/Validator/NotDisposableEmail.php
  use Pyzit\TempMail\TempMailClient;
  use Symfony\Component\Validator\Constraint;
  use Symfony\Component\Validator\ConstraintValidator;

  class NotDisposableEmailValidator extends ConstraintValidator
  {
      public function __construct(private TempMailClient $client) {}

      public function validate(mixed $value, Constraint $constraint): void
      {
          try {
              $r = $this->client->check((string)$value);
              if ($r->isDisposable) {
                  $this->context->buildViolation('Disposable emails are not allowed.')->addViolation();
              }
          } catch (\Exception) { /* fail open */ }
      }
  }

  ── Plain PHP ─────────────────────────────────────────────────
  $client = new TempMailClient($_ENV['PYZIT_TOKEN']);
  $r = $client->check($_POST['email'] ?? '');
  if ($r->isDisposable) {
      http_response_code(422);
      echo json_encode(['error' => 'Disposable emails are not allowed.']);
      exit;
  }

CODE;


// ── DONE ─────────────────────────────────────────────────────────

echo "\n" . str_repeat('═', 60) . "\n";
echo "  All examples complete.\n";
echo str_repeat('═', 60) . "\n\n";