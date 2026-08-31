<?php

declare(strict_types=1);

namespace App\Services\Communication;

/**
 * Channel delivery decisions returned by CommunicationPolicyService.
 */
final class CommunicationDeliveryDecision
{
    public const IMMEDIATE =
    'IMMEDIATE';

    public const DAILY =
    'DAILY';

    public const WEEKLY =
    'WEEKLY';

    public const SKIP =
    'SKIP';

    private function __construct() {}
}
