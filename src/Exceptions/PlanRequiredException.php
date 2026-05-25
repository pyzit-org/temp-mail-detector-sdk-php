<?php

declare(strict_types=1);

namespace Pyzit\TempMail\Exceptions;

/**
 * Thrown when the API returns HTTP 402 or a plan-related 403.
 * The requested endpoint requires a higher subscription plan.
 */
class PlanRequiredException extends PyzitException
{
    public function __construct(private readonly string $requiredPlan = 'pro')
    {
        parent::__construct("This endpoint requires the '{$requiredPlan}' plan or higher.");
    }

    public function getRequiredPlan(): string
    {
        return $this->requiredPlan;
    }
}