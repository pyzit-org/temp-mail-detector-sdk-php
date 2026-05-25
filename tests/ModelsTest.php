<?php declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

use PHPUnit\Framework\TestCase;
use Pyzit\TempMail\Models\BulkResult;
use Pyzit\TempMail\Models\CheckResult;
use Pyzit\TempMail\Models\DetailedResult;
use Pyzit\TempMail\Models\DnsIntelligence;
use Pyzit\TempMail\Models\MxRecord;
use Pyzit\TempMail\Models\ReputationDetail;
use Pyzit\TempMail\Models\Signals;
use Pyzit\TempMail\Models\StabilityInfo;

class ModelsTest extends TestCase
{
    // ── CheckResult ───────────────────────────────────────────────

    public function testCheckResultCleanEmail(): void
    {
        $r = CheckResult::fromArray(Fixtures::cleanPayload());
        $this->assertSame('hi@pyzit.com', $r->email);
        $this->assertFalse($r->isDisposable);
        $this->assertSame('clean', $r->status);
        $this->assertTrue($r->isClean());
    }

    public function testCheckResultDisposableEmail(): void
    {
        $r = CheckResult::fromArray(Fixtures::disposablePayload());
        $this->assertSame('user@mailnator.com', $r->email);
        $this->assertTrue($r->isDisposable);
        $this->assertSame('disposable', $r->status);
        $this->assertFalse($r->isClean());
    }

    public function testCheckResultHandlesMissingFields(): void
    {
        $r = CheckResult::fromArray([]);
        $this->assertSame('', $r->email);
        $this->assertFalse($r->isDisposable);
        $this->assertSame('unknown', $r->status);
    }

    // ── DetailedResult ────────────────────────────────────────────

    public function testDetailedResultTopLevel(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $this->assertSame('x@new-domain.io', $r->email);
        $this->assertSame('new-domain.io', $r->domain);
        $this->assertTrue($r->isDisposable);
        $this->assertSame('disposable', $r->status);
        $this->assertSame(0.0, $r->reputationScore);
        $this->assertSame('high', $r->riskLevel);
        $this->assertSame('reject', $r->recommendation);
    }

    public function testDetailedResultShouldReject(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $this->assertTrue($r->shouldReject());
    }

    public function testDetailedResultShouldNotRejectClean(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedWithMxPayload());
        $this->assertFalse($r->shouldReject());
    }

    public function testDetailedResultReputation(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $rep = $r->details->reputation;
        $this->assertInstanceOf(ReputationDetail::class, $rep);
        $this->assertSame(0.0, $rep->reputationScore);
        $this->assertTrue($rep->isDisposable);
        $this->assertSame(0.79, $rep->disposableConfidence);
        $this->assertSame('high', $rep->riskLevel);
        $this->assertSame('reject', $rep->recommendation);
    }

    public function testDetailedResultSignals(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $sig = $r->details->signals;
        $this->assertInstanceOf(Signals::class, $sig);
        $this->assertEmpty($sig->positive);
        $this->assertContains('no_mx_records', $sig->negative);
        $this->assertContains('new_domain', $sig->negative);
        $this->assertContains('limited_history', $sig->neutral);
    }

    public function testDetailedResultDnsNoMx(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $dns = $r->details->dnsIntelligence;
        $this->assertInstanceOf(DnsIntelligence::class, $dns);
        $this->assertFalse($dns->hasMx);
        $this->assertCount(0, $dns->mxRecords);
        $this->assertFalse($dns->hasSpf);
        $this->assertFalse($dns->hasDmarc);
        $this->assertNull($dns->error);
    }

    public function testDetailedResultDnsWithMxRecords(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedWithMxPayload());
        $dns = $r->details->dnsIntelligence;
        $this->assertTrue($dns->hasMx);
        $this->assertCount(2, $dns->mxRecords);

        $first = $dns->mxRecords[0];
        $this->assertInstanceOf(MxRecord::class, $first);
        $this->assertSame(5, $first->priority);
        $this->assertSame('mail1.pyzit.com', $first->exchange);
        $this->assertContains('172.65.182.103', $first->ips);

        $second = $dns->mxRecords[1];
        $this->assertSame(10, $second->priority);
        $this->assertSame('mail2.pyzit.com', $second->exchange);
    }

    public function testDetailedResultStability(): void
    {
        $r = DetailedResult::fromArray(Fixtures::detailedPayload());
        $stab = $r->details->stability;
        $this->assertInstanceOf(StabilityInfo::class, $stab);
        $this->assertSame(0.2, $stab->stabilityScore);
        $this->assertSame(0, $stab->domainAgeDays);
        $this->assertTrue($stab->isNewDomain);
        $this->assertContains('newly_observed_domain', $stab->riskFactors);
        $this->assertContains('no_mx_records', $stab->riskFactors);
    }

    // ── BulkResult ────────────────────────────────────────────────

    public function testBulkResultResultsMap(): void
    {
        $r = BulkResult::fromArray(Fixtures::bulkPayload());
        $this->assertSame(4, $r->processed);
        $this->assertFalse($r->results['hi@pyzit.com']);
        $this->assertTrue($r->results['x@mailnator.com']);
        $this->assertFalse($r->results['support@github.com']);
        $this->assertTrue($r->results['fake@temp-mail.org']);
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

    public function testBulkResultEmpty(): void
    {
        $r = BulkResult::fromArray(['results' => [], 'processed' => 0]);
        $this->assertSame(0, $r->processed);
        $this->assertEmpty($r->disposableEmails());
        $this->assertEmpty($r->cleanEmails());
    }

    // ── MxRecord ──────────────────────────────────────────────────

    public function testMxRecordFromArray(): void
    {
        $mx = MxRecord::fromArray([
            'priority' => 10,
            'exchange' => 'mail.example.com',
            'ips' => ['1.2.3.4', '5.6.7.8'],
        ]);
        $this->assertSame(10, $mx->priority);
        $this->assertSame('mail.example.com', $mx->exchange);
        $this->assertContains('1.2.3.4', $mx->ips);
        $this->assertContains('5.6.7.8', $mx->ips);
    }

    public function testMxRecordDefaults(): void
    {
        $mx = MxRecord::fromArray([]);
        $this->assertSame(0, $mx->priority);
        $this->assertSame('', $mx->exchange);
        $this->assertEmpty($mx->ips);
    }

    // ── DetailedResult — shouldChallenge / shouldAccept / normalization ──

    public function testShouldChallengeReturnsTrueForChallenge(): void
    {
        $data = Fixtures::detailedPayload();
        $data['recommendation'] = 'challenge';
        $r = DetailedResult::fromArray($data);
        $this->assertTrue($r->shouldChallenge());
        $this->assertFalse($r->shouldReject());
        $this->assertFalse($r->shouldAccept());
    }

    public function testShouldChallengeNormalizesUppercase(): void
    {
        $data = Fixtures::detailedPayload();
        $data['recommendation'] = 'CHALLENGE';
        $r = DetailedResult::fromArray($data);
        $this->assertSame('challenge', $r->recommendation);
        $this->assertTrue($r->shouldChallenge());
    }

    public function testShouldAcceptReturnsTrueForAccept(): void
    {
        $data = Fixtures::detailedWithMxPayload();
        $r = DetailedResult::fromArray($data);
        $this->assertTrue($r->shouldAccept());
        $this->assertFalse($r->shouldReject());
        $this->assertFalse($r->shouldChallenge());
    }

    public function testRecommendationIsAlwaysLowercased(): void
    {
        foreach (['REJECT', 'Reject', 'ACCEPT', 'CHALLENGE', 'REVIEW'] as $raw) {
            $data = Fixtures::detailedPayload();
            $data['recommendation'] = $raw;
            $r = DetailedResult::fromArray($data);
            $this->assertSame(strtolower($raw), $r->recommendation);
        }
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
