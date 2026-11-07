<?php declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\Models\BulkResult;
use Pyzit\TempMail\Models\CheckResult;
use Pyzit\TempMail\Models\DetailedDetails;
use Pyzit\TempMail\Models\DetailedResult;

class ModelsTest extends TestCase
{
    // ── CheckResult ───────────────────────────────────────────────

    public function testCheckResultCleanEmail(): void
    {
        $r = CheckResult::fromArray(Fixtures::cleanPayload());
        $this->assertSame('hi@pyzit.com', $r->email);
        $this->assertFalse($r->isDisposable);
        $this->assertFalse($r->isPrivacyRelay);
        $this->assertSame('clean', $r->status);
        $this->assertTrue($r->isClean());
    }

    public function testCheckResultDisposableEmail(): void
    {
        $r = CheckResult::fromArray(Fixtures::disposablePayload());
        $this->assertSame('user@mailnator.com', $r->email);
        $this->assertTrue($r->isDisposable);
        $this->assertFalse($r->isPrivacyRelay);
        $this->assertSame('disposable', $r->status);
        $this->assertFalse($r->isClean());
    }

    public function testCheckResultPrivacyRelay(): void
    {
        $r = CheckResult::fromArray(Fixtures::privacyRelayPayload());
        $this->assertSame('user@privaterelay.appleid.com', $r->email);
        $this->assertFalse($r->isDisposable);
        $this->assertTrue($r->isPrivacyRelay);
        $this->assertSame('privacy_relay', $r->status);
        $this->assertSame('apple', $r->relayProvider);
        $this->assertSame('Apple Private Relay', $r->providerName);
        $this->assertTrue($r->isClean());
    }

    public function testCheckResultHandlesMissingFields(): void
    {
        $r = CheckResult::fromArray([]);
        $this->assertSame('', $r->email);
        $this->assertFalse($r->isDisposable);
        $this->assertFalse($r->isPrivacyRelay);
        $this->assertSame('clean', $r->status);
    }

    // ── DetailedResult ────────────────────────────────────────────

    public function testDetailedResultTopLevel(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $this->assertSame('x@new-domain.io', $r->email);
        $this->assertSame('new-domain.io', $r->domain);
        $this->assertTrue($r->isDisposable);
        $this->assertSame('disposable', $r->status);
        $this->assertSame(0, $r->reputationScore);
        $this->assertSame('high', $r->riskLevel);
        $this->assertFalse($r->cached);
    }

    public function testDetailedResultShouldReject(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $this->assertTrue($r->shouldReject());
        $this->assertTrue($r->isHighRisk());
    }

    public function testDetailedResultShouldNotRejectClean(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedWithMxPayload());
        $this->assertFalse($r->shouldReject());
        $this->assertTrue($r->isTrustedDomain());
        $this->assertTrue($r->isClean());
    }

    public function testDetailedResultDnsIntelligenceNoMx(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $this->assertInstanceOf(DetailedDetails::class, $r->details);
        $this->assertFalse($r->hasMx());
        $this->assertCount(0, $r->mxHostnames());
        $this->assertFalse($r->details->hasSpf);
        $this->assertFalse($r->details->hasDmarc);
    }

    public function testDetailedResultDnsWithMxRecords(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedWithMxPayload());
        $this->assertTrue($r->hasMx());
        $this->assertCount(2, $r->mxHostnames());
        $this->assertSame(['mail1.pyzit.com', 'mail2.pyzit.com'], $r->mxHostnames());
        $this->assertTrue($r->details->hasA);
        $this->assertSame(['172.65.182.103', '172.65.182.104'], $r->aIps());
        $this->assertTrue($r->details->hasSpf);
        $this->assertSame('v=spf1 include:custom-whitelist ~all', $r->details->spfRecord);
        $this->assertTrue($r->details->hasDmarc);
        $this->assertSame('v=DMARC1; p=none', $r->details->dmarcRecord);
    }

    public function testDetailedResultShouldFlag(): void
    {
        $payload = Fixtures::detailedPayload();
        $payload['classification'] = 'suspicious';
        $r = DetailedResult::fromArray($payload);
        $this->assertTrue($r->shouldFlag());
    }

    // ── BulkResult ────────────────────────────────────────────────

    public function testBulkResultParsing(): void
    {
        $r = BulkResult::fromArray(Fixtures::bulkPayload());
        $this->assertSame(4, $r->total);
        $this->assertSame('mixed', $r->status);
        $this->assertSame(2, $r->disposableCount);
        $this->assertSame(0, $r->privacyRelayCount);
        $this->assertCount(4, $r->results);
        $this->assertInstanceOf(CheckResult::class, $r->results[0]);
        $this->assertSame('hi@pyzit.com', $r->results[0]->email);
        $this->assertFalse($r->results[0]->isDisposable);
    }

    public function testBulkResultDisposableEmails(): void
    {
        $r = BulkResult::fromArray(Fixtures::bulkPayload());
        $disp = $r->disposableEmails();
        $this->assertContains('x@mailnator.com', $disp);
        $this->assertContains('fake@temp-mail.org', $disp);
        $this->assertNotContains('hi@pyzit.com', $disp);
    }

    public function testBulkResultCleanEmails(): void
    {
        $r = BulkResult::fromArray(Fixtures::bulkPayload());
        $clean = $r->cleanEmails();
        $this->assertContains('hi@pyzit.com', $clean);
        $this->assertContains('support@github.com', $clean);
        $this->assertNotContains('x@mailnator.com', $clean);
    }

    public function testBulkResultPrivacyRelayEmails(): void
    {
        $r = BulkResult::fromArray(Fixtures::bulkWithRelayPayload());
        $this->assertSame(3, $r->total);
        $this->assertSame(1, $r->privacyRelayCount);
        $this->assertSame(['user@privaterelay.appleid.com'], $r->privacyRelayEmails());
        $this->assertSame(['x@mailnator.com'], $r->disposableEmails());
        $this->assertSame(['hi@pyzit.com', 'user@privaterelay.appleid.com'], $r->cleanEmails());
    }

    public function testBulkResultEmpty(): void
    {
        $r = BulkResult::fromArray(['results' => [], 'total' => 0]);
        $this->assertSame(0, $r->total);
        $this->assertEmpty($r->disposableEmails());
        $this->assertEmpty($r->cleanEmails());
        $this->assertEmpty($r->privacyRelayEmails());
    }

    public function testRiskLevelIsAlwaysLowercased(): void
    {
        foreach (['HIGH', 'MEDIUM', 'LOW'] as $raw) {
            $data = Fixtures::detailedPayload();
            $data['risk_level'] = $raw;
            $r = DetailedResult::fromArray($data);
            $this->assertSame(strtolower($raw), $r->riskLevel);
        }
    }
}
