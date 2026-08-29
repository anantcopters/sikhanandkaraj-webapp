<?php

declare(strict_types=1);

use App\Services\Membership\MembershipLifecycleService;
use CodeIgniter\Boot;
use Config\Paths;

/*
|--------------------------------------------------------------------------
| CLI restriction
|--------------------------------------------------------------------------
|
| Membership lifecycle housekeeping must never be executable through
| the web server.
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI access only.');
}

/*
|--------------------------------------------------------------------------
| Resolve and boot CodeIgniter
|--------------------------------------------------------------------------
|
| Follow the same CLI bootstrap pattern already used by the application's
| maintenance scripts. We deliberately do not use Spark.
*/
$projectRoot = dirname(__DIR__);

define(
    'FCPATH',
    $projectRoot
        . DIRECTORY_SEPARATOR
        . 'public'
        . DIRECTORY_SEPARATOR
);

chdir($projectRoot);

/*
 * QA/Production normally provide CI_ENVIRONMENT externally.
 *
 * Keep the same safe production fallback used by the existing maintenance
 * scripts.
 */
if (!defined('ENVIRONMENT')) {
    define(
        'ENVIRONMENT',
        getenv('CI_ENVIRONMENT')
            ?: 'production'
    );
}

require $projectRoot
    . DIRECTORY_SEPARATOR
    . 'app'
    . DIRECTORY_SEPARATOR
    . 'Config'
    . DIRECTORY_SEPARATOR
    . 'Paths.php';

$paths = new Paths();

require $paths->systemDirectory
    . DIRECTORY_SEPARATOR
    . 'Boot.php';

Boot::bootConsole(
    $paths
);

/*
|--------------------------------------------------------------------------
| Membership lifecycle
|--------------------------------------------------------------------------
|
| Usage:
|
| php scripts/membership_lifecycle.php
| php scripts/membership_lifecycle.php 500
|
| The optional argument controls the maximum number of expired memberships
| processed in one execution.
|
| IMPORTANT:
|
| This job is housekeeping only. MembershipService continues checking
| starts_at/expires_at during every runtime membership resolution. Therefore
| a delayed or failed cron cannot extend paid access.
*/

$batchSize =
    isset($argv[1])
    ? (int) $argv[1]
    : 500;

$batchSize = max(
    1,
    min(
        1000,
        $batchSize
    )
);

try {
    /** @var MembershipLifecycleService $membershipLifecycleService */
    $membershipLifecycleService = service(
        'membershipLifecycleService'
    );

    $result =
        $membershipLifecycleService
        ->expireDueMemberships(
            $batchSize
        );

    echo PHP_EOL;
    echo 'Membership lifecycle complete' . PHP_EOL;
    echo '=============================' . PHP_EOL;

    echo 'Scanned:   '
        . (int) (
            $result['scanned']
            ?? 0
        )
        . PHP_EOL;

    echo 'Expired:   '
        . (int) (
            $result['expired']
            ?? 0
        )
        . PHP_EOL;

    echo 'Failed:    '
        . (int) (
            $result['failed']
            ?? 0
        )
        . PHP_EOL;

    echo 'Remaining: '
        . (int) (
            $result['remaining']
            ?? 0
        )
        . PHP_EOL;

    /*
     * Return a non-zero exit code if individual rows failed.
     *
     * This allows cron/server monitoring to identify a partially failed run
     * without affecting successful membership expirations.
     */
    if (
        (int) (
            $result['failed']
            ?? 0
        ) > 0
    ) {
        exit(1);
    }

    exit(0);
} catch (Throwable $exception) {
    /*
     * The exception is also written to the normal application error log.
     */
    log_message(
        'error',
        'Membership lifecycle failed: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        'Membership lifecycle failed: '
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
