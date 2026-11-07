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
            'email'            => 'hi@pyzit.com',
            'is_disposable'    => false,
            'is_privacy_relay' => false,
            'status'           => 'clean',
        ];
    }

    public static function disposablePayload(): array
    {
        return [
            'email'            => 'user@mailnator.com',
            'is_disposable'    => true,
            'is_privacy_relay' => false,
            'status'           => 'disposable',
        ];
    }

    public static function privacyRelayPayload(): array
    {
        return [
            'email'            => 'user@privaterelay.appleid.com',
            'is_disposable'    => false,
            'is_privacy_relay' => true,
            'status'           => 'privacy_relay',
            'relay_provider'   => 'apple',
            'provider_name'    => 'Apple Private Relay',
        ];
    }

    public static function detailedPayload(): array
    {
        return [
            'email'            => 'x@new-domain.io',
            'domain'           => 'new-domain.io',
            'is_disposable'    => true,
            'is_trusted'       => false,
            'is_privacy_relay' => false,
            'classification'   => 'disposable',
            'reputation_score' => 0,
            'risk_level'       => 'high',
            'is_valid_format'  => true,
            'cached'           => false,
            'rule_applied'     => null,
            'details'          => [
                'has_mx'       => false,
                'mx_records'   => [],
                'has_a'        => false,
                'a_records'    => [],
                'has_spf'      => false,
                'spf_record'   => null,
                'has_dmarc'    => false,
                'dmarc_record' => null,
            ],
            'status'           => 'disposable',
        ];
    }

    public static function detailedWithMxPayload(): array
    {
        return [
            'email'            => 'hi@pyzit.com',
            'domain'           => 'pyzit.com',
            'is_disposable'    => false,
            'is_trusted'       => true,
            'is_privacy_relay' => false,
            'classification'   => 'trusted',
            'reputation_score' => 100,
            'risk_level'       => 'low',
            'is_valid_format'  => true,
            'cached'           => false,
            'rule_applied'     => 'custom_whitelist',
            'details'          => [
                'has_mx'       => true,
                'mx_records'   => ['mail1.pyzit.com', 'mail2.pyzit.com'],
                'has_a'        => true,
                'a_records'    => ['172.65.182.103', '172.65.182.104'],
                'has_spf'      => true,
                'spf_record'   => 'v=spf1 include:custom-whitelist ~all',
                'has_dmarc'    => true,
                'dmarc_record' => 'v=DMARC1; p=none',
            ],
            'status'           => 'clean',
        ];
    }

    public static function bulkPayload(): array
    {
        return [
            'total'               => 4,
            'status'              => 'mixed',
            'disposable_count'    => 2,
            'privacy_relay_count' => 0,
            'flagged_domains'     => ['mailnator.com', 'temp-mail.org'],
            'results'             => [
                [
                    'email'            => 'hi@pyzit.com',
                    'is_disposable'    => false,
                    'is_privacy_relay' => false,
                    'status'           => 'clean',
                ],
                [
                    'email'            => 'x@mailnator.com',
                    'is_disposable'    => true,
                    'is_privacy_relay' => false,
                    'status'           => 'disposable',
                ],
                [
                    'email'            => 'support@github.com',
                    'is_disposable'    => false,
                    'is_privacy_relay' => false,
                    'status'           => 'clean',
                ],
                [
                    'email'            => 'fake@temp-mail.org',
                    'is_disposable'    => true,
                    'is_privacy_relay' => false,
                    'status'           => 'disposable',
                ],
            ],
        ];
    }

    public static function bulkWithRelayPayload(): array
    {
        return [
            'total'               => 3,
            'status'              => 'mixed',
            'disposable_count'    => 1,
            'privacy_relay_count' => 1,
            'flagged_domains'     => ['mailnator.com'],
            'results'             => [
                [
                    'email'            => 'hi@pyzit.com',
                    'is_disposable'    => false,
                    'is_privacy_relay' => false,
                    'status'           => 'clean',
                ],
                [
                    'email'            => 'x@mailnator.com',
                    'is_disposable'    => true,
                    'is_privacy_relay' => false,
                    'status'           => 'disposable',
                ],
                [
                    'email'            => 'user@privaterelay.appleid.com',
                    'is_disposable'    => false,
                    'is_privacy_relay' => true,
                    'status'           => 'privacy_relay',
                    'relay_provider'   => 'apple',
                    'provider_name'    => 'Apple Private Relay',
                ],
            ],
        ];
    }
}
