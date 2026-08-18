<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Raised before restricted profile information is loaded.
 */
final class PaidMembershipRequiredException extends DomainException {}
