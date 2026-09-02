<?php

declare(strict_types=1);

namespace App\Services\Communication;

/**
 * Channel-independent communication categories.
 *
 * Categories describe the purpose of communication, not the delivery
 * channel. Email, SMS and WhatsApp must therefore reuse these values
 * instead of defining their own category constants.
 */
final class CommunicationCategory
{
    public const MODERATION =
    'MODERATION';

    public const VERIFICATION =
    'VERIFICATION';

    public const SECURITY =
    'SECURITY';

    public const MATRIMONIAL_ACTIVITY =
    'MATRIMONIAL_ACTIVITY';

    public const TRANSACTIONAL =
    'TRANSACTIONAL';

    public const ENGAGEMENT =
    'ENGAGEMENT';

    public const SUPPORT =
    'SUPPORT';

    public const MEMBERSHIP =
    'MEMBERSHIP';

    private function __construct() {}
}
