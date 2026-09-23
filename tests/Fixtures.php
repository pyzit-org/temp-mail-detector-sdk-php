<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Tests;

/**
 * Shared test data and helpers — imported by every test class.
 */
class Fixtures
{
    public const FAKE_TOKEN   = 'pyzit_live_test-token-xyz';
    public const BASE_URL     = 'https://api.pyzit.com';
    public const CHECK_URL    = self::BASE_URL . '/v1/tmd/check/';
    public const DETAILED_URL = self::BASE_URL . '/v1/tmd/detailed/';
    public const BULK_URL     = self::BASE_URL . '/v1/tmd/bulk/';

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
            'is_trusted'       => false,
            'classification'   => 'disposable',
            'reputation_score' => 0,
            'risk_level'       => 'high',
            'is_valid_format'  => true,
            'rule_applied'     => null,
            'details'          => [
                'reputation' => [
                    'reputation_score' => 0,
                    'is_disposable' => true,
                    'disposable_confidence' => 0.79,
                    'risk_level' => 'high',
                    'recommendation' => 'reject',
                ],
                'signals' => [
                    'positive' => [],
                    'negative' => ['no_mx_records', 'new_domain'],
                    'neutral' => ['limited_history'],
                ],
                'dns_intelligence' => [
                    'has_mx' => false,
                    'mx_records' => [],
                    'has_a_record' => false,
                    'has_spf' => false,
                    'has_dmarc' => false,
                    'error' => null,
                ],
                'stability' => [
                    'stability_score' => 0.2,
                    'domain_age_days' => 0,
                    'is_new_domain' => true,
                    'risk_factors' => ['newly_observed_domain', 'no_mx_records'],
                ],
            ],
            'status'           => 'disposable',
            'recommendation'   => 'reject',
        ];
    }

    public static function detailedWithMxPayload(): array
    {
        $payload = self::detailedPayload();
        $payload['domain']           = 'pyzit.com';
        $payload['is_disposable']    = false;
        $payload['is_trusted']       = true;
        $payload['classification']   = 'trusted';
        $payload['reputation_score'] = 100;
        $payload['risk_level']       = 'low';
        $payload['is_valid_format']  = true;
        $payload['rule_applied']     = 'custom_whitelist';
        $payload['details'] = [
            'reputation' => [
                'reputation_score' => 100,
                'is_disposable' => false,
                'disposable_confidence' => 0.0,
                'risk_level' => 'low',
                'recommendation' => 'accept',
            ],
            'signals' => [
                'positive' => ['trusted_domain'],
                'negative' => [],
                'neutral' => [],
            ],
            'dns_intelligence' => [
                'has_mx' => true,
                'mx_records' => [
                    [
                        'priority' => 5,
                        'exchange' => 'mail1.pyzit.com',
                        'ips' => ['172.65.182.103'],
                    ],
                    [
                        'priority' => 10,
                        'exchange' => 'mail2.pyzit.com',
                        'ips' => [],
                    ],
                ],
                'has_a_record' => true,
                'has_spf' => true,
                'has_dmarc' => true,
                'error' => null,
            ],
            'stability' => [
                'stability_score' => 0.9,
                'domain_age_days' => 365,
                'is_new_domain' => false,
                'risk_factors' => [],
            ],
        ];
        $payload['status']           = 'clean';
        $payload['recommendation']   = 'accept';
        return $payload;
    }

    public static function bulkPayload(): array
    {
        return [
            'processed' => 4,
            'status' => 'mixed',
            'disposable_count' => 2,
            'flagged_domains' => ['mailnator.com', 'temp-mail.org'],
            'results' => [
                [
                    'email' => 'hi@pyzit.com',
                    'is_disposable' => false,
                    'status' => 'clean',
                ],
                [
                    'email' => 'x@mailnator.com',
                    'is_disposable' => true,
                    'status' => 'disposable',
                ],
                [
                    'email' => 'support@github.com',
                    'is_disposable' => false,
                    'status' => 'clean',
                ],
                [
                    'email' => 'fake@temp-mail.org',
                    'is_disposable' => true,
                    'status' => 'disposable',
                ],
            ],
        ];
    }
}