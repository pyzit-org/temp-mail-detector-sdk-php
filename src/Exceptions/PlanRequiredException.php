<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Raised on HTTP 402 or 403 when a higher plan is required.
 */
final class PlanRequiredException extends PyzitException
{
    private string $requiredPlan;

    public function __construct(string $requiredPlan = "pro", string $message = "")
    {
        $this->requiredPlan = $requiredPlan;
        parent::__construct(
            $message ?: "This feature requires the {$requiredPlan} plan or higher."
        );
    }

    public function getRequiredPlan(): string
    {
        return $this->requiredPlan;
    }
}
