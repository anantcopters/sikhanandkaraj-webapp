<?php

declare(strict_types=1);

namespace App\Services\Registration;

/**
 * Identifies the result of a Register Free request.
 */
enum RegistrationAction: string
{
    /**
     * A completely new pending account was created.
     */
    case CREATED = 'CREATED';

    /**
     * An existing unverified registration was updated.
     */
    case PENDING_UPDATED = 'PENDING_UPDATED';

    /**
     * The mobile belongs to an existing verified account.
     */
    case VERIFIED_MOBILE_EXISTS = 'VERIFIED_MOBILE_EXISTS';

    /**
     * CHANGE:
     * The mobile number has reached the registration OTP-send limit.
     */
    case OTP_LIMIT_REACHED = 'OTP_LIMIT_REACHED';
}

