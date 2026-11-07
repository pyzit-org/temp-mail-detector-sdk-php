<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Models;

/**
 * Result from the /v1/tmd/check/ endpoint.
 */
final class CheckResult
{
    /**
     * @param array<string,mixed>|null $ruleDetails
     */
    public function __construct(
        /** The email address that was validated. */
        public readonly string $email,
        /** True if the email is from a disposable/throwaway provider. */
        public readonly bool $isDisposable,
        /** "clean", "disposable", or "privacy_relay" */
        public readonly string $status,
        /** True if the email is from a privacy relay provider. */
        public readonly bool $isPrivacyRelay = false,
        /** Provider slug (e.g. "apple", "firefox"), if privacy relay. */
        public readonly ?string $relayProvider = null,
        /** Provider display name, if privacy relay. */
        public readonly ?string $providerName = null,
        /** Enterprise custom rule applied, if any. */
        public readonly ?string $ruleApplied = null,
        /** Enterprise custom rule configuration, if any. */
        public readonly ?array $ruleDetails = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $isDisposable = (bool)($data['is_disposable'] ?? false);
        $isPrivacyRelay = (bool)($data['is_privacy_relay'] ?? false);
        $status = (string)($data['status'] ?? ($isPrivacyRelay ? 'privacy_relay' : ($isDisposable ? 'disposable' : 'clean')));

        return new self(
            email:          (string)($data['email'] ?? ''),
            isDisposable:   $isDisposable,
            status:         $status,
            isPrivacyRelay: $isPrivacyRelay,
            relayProvider:  isset($data['relay_provider']) ? (string)$data['relay_provider'] : null,
            providerName:   isset($data['provider_name']) ? (string)$data['provider_name'] : null,
            ruleApplied:    isset($data['rule_applied']) ? (string)$data['rule_applied'] : null,
            ruleDetails:    isset($data['rule_details']) && is_array($data['rule_details']) ? $data['rule_details'] : null,
        );
    }

    /** Convenience — true when email is safe to accept (not disposable). */
    public function isClean(): bool
    {
        return !$this->isDisposable;
    }
}
