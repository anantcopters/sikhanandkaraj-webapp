<?php

declare(strict_types=1);

/**
 * Generic production exception view.
 *
 * CI4 loads this file for unhandled production exceptions. The actual
 * exception message and stack trace must never be exposed to users.
 */

require __DIR__ . DIRECTORY_SEPARATOR . 'error_500.php';