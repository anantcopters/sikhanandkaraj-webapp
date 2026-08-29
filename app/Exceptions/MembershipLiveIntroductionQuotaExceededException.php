<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Raised when a paid member has exhausted the purchased Live Introduction
 * allowance for the current membership.
 *
 * Keeping quota exhaustion distinct from ordinary access denial lets the
 * controller return the correct customer-facing message without parsing
 * exception text.
 */
final class MembershipLiveIntroductionQuotaExceededException
extends DomainException {}
