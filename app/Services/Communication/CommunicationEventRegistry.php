<?php

declare(strict_types=1);

namespace App\Services\Communication;

/**
 * Stable business-event identifiers for communication orchestration.
 *
 * These identifiers describe business events and therefore must not
 * contain channel names such as EMAIL, SMS or WHATSAPP.
 */
final class CommunicationEventRegistry
{
    public const INTEREST_RECEIVED =
    'INTEREST_RECEIVED';

    public const INTEREST_ACCEPTED =
    'INTEREST_ACCEPTED';

    public const INTEREST_DECLINED =
    'INTEREST_DECLINED';

    public const PROFILE_VIEWED =
    'PROFILE_VIEWED';

    public const PROFILE_SHORTLISTED =
    'PROFILE_SHORTLISTED';

    public const PHOTO_REJECTED =
    'PHOTO_REJECTED';

    public const AADHAAR_APPROVED =
    'AADHAAR_APPROVED';

    public const AADHAAR_REJECTED =
    'AADHAAR_REJECTED';

    public const VIDEO_APPROVED =
    'VIDEO_APPROVED';

    public const VIDEO_REJECTED =
    'VIDEO_REJECTED';

    public const VIDEO_RESUBMISSION_REQUESTED =
    'VIDEO_RESUBMISSION_REQUESTED';

    public const SUPPORT_RECEIVED =
    'SUPPORT_RECEIVED';

    public const SUPPORT_RESOLVED =
    'SUPPORT_RESOLVED';

    public const MEMBERSHIP_ACTIVATED =
    'MEMBERSHIP_ACTIVATED';

    public const MEMBERSHIP_EXPIRING_SOON =
    'MEMBERSHIP_EXPIRING_SOON';

    public const MEMBERSHIP_EXPIRED =
    'MEMBERSHIP_EXPIRED';

    private function __construct() {}
}
