<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Raised when a paid member has exhausted a Full Profile allowance.
 */
final class MembershipProfileQuotaExceededException
extends DomainException {}
