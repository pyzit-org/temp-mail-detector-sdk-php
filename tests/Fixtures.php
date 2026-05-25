<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

/**
 * Shared test data and helpers — imported by every test class.
 */
class Fixtures
{
    public const FAKE_TOKEN   = 'test-token-xyz';
    public const BASE_URL     = 'https://api-tempmail.pyzit.com/v1';
    public const CHECK_URL    = self::BASE_URL . '/validate/check/';
    public const DETAILED_URL = self::BASE_URL . '/validate/detailed/';
    public const BULK_URL     = self::BASE_URL . '/validate/bulk/';

    // ── response payloads ─────────────────────────────────────────

    public static function cleanPayload(): array
    {
        return [
            'email'         => 'hi@pyzit.com',
            'is_disposable' => false,
            'status'        => 'clean',
        ];
    }

    public static function disposablePayload(): array
    {
        return [
            'email'         => 'user@mailnator.com',
            'is_disposable' => true,
            'status'        => 'disposable',
        ];
    }

    public static function detailedPayload(): array
    {
        return [
            'email'            => 'x@new-domain.io',
            'domain'           => 'new-domain.io',
            'is_disposable'    => true,
            'status'           => 'disposable',
            'reputation_score' => 0.0,
            'risk_level'       => 'high',
            'recommendation'   => 'reject',
            'details'          => [
                'reputation' => [
                    'reputation_score'      => 0.0,
                    'is_disposable'         => true,
                    'disposable_confidence' => 0.79,
                    'risk_level'            => 'high',
                    'recommendation'        => 'reject',
                ],
                'signals' => [
                    'positive' => [],
                    'negative' => ['no_mx_records', 'new_domain', 'smtp_server_unreachable'],
                    'neutral'  => ['limited_history', 'smtp_rejected_probe'],
                ],
                'dns_intelligence' => [
                    'has_mx'       => false,
                    'mx_records'   => [],
                    'has_a_record' => false,
                    'has_spf'      => false,
                    'has_dmarc'    => false,
                    'error'        => null,
                ],
                'stability' => [
                    'stability_score' => 0.2,
                    'domain_age_days' => 0,
                    'is_new_domain'   => true,
                    'risk_factors'    => ['newly_observed_domain', 'no_mx_records'],
                ],
            ],
        ];
    }

    public static function detailedWithMxPayload(): array
    {
        $payload = self::detailedPayload();
        $payload['domain']           = 'pyzit.com';
        $payload['is_disposable']    = false;
        $payload['status']           = 'clean';
        $payload['reputation_score'] = 0.9;
        $payload['risk_level']       = 'low';
        $payload['recommendation']   = 'accept';
        $payload['details']['dns_intelligence'] = [
            'has_mx'       => true,
            'mx_records'   => [
                ['priority' => 5,  'exchange' => 'mail1.pyzit.com', 'ips' => ['172.65.182.103']],
                ['priority' => 10, 'exchange' => 'mail2.pyzit.com', 'ips' => ['172.65.182.104']],
            ],
            'has_a_record' => true,
            'has_spf'      => true,
            'has_dmarc'    => true,
            'error'        => null,
        ];
        return $payload;
    }

    public static function bulkPayload(): array
    {
        return [
            'results' => [
                'hi@pyzit.com'       => false,
                'x@mailnator.com'    => true,
                'support@github.com' => false,
                'fake@temp-mail.org' => true,
            ],
            'processed' => 4,
        ];
    }
}