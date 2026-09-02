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
| Membership reminder processing must never be executable through
| the web server.
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(
        403
    );

    exit('CLI access only.');
}

/*
|--------------------------------------------------------------------------
| Resolve and boot CodeIgniter
|--------------------------------------------------------------------------
|
| Follow the same standalone CLI bootstrap pattern already used by:
|
| - scripts/email_worker.php
| - scripts/email_queue_cleanup.php
| - scripts/membership_lifecycle.php
|
| Do not introduce a separate Spark command architecture for one job.
*/
$projectRoot =
    dirname(
        __DIR__
    );

define(
    'FCPATH',
    $projectRoot
        . DIRECTORY_SEPARATOR
        . 'public'
        . DIRECTORY_SEPARATOR
);

chdir(
    $projectRoot
);

/*
 * QA/Production normally provide CI_ENVIRONMENT externally.
 *
 * Keep the same production fallback used by the existing
 * maintenance scripts.
 */
if (!defined('ENVIRONMENT')) {
    define(
        'ENVIRONMENT',
        getenv(
            'CI_ENVIRONMENT'
        )
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

$paths =
    new Paths();

require $paths
    ->systemDirectory
    . DIRECTORY_SEPARATOR
    . 'Boot.php';

Boot::bootConsole(
    $paths
);

/*
|--------------------------------------------------------------------------
| Membership expiry reminders
|--------------------------------------------------------------------------
|
| Usage:
|
| php scripts/membership_expiry_reminders.php
| php scripts/membership_expiry_reminders.php 500
|
| The job finds ACTIVE memberships expiring exactly three member-facing
| calendar days from today.
|
| MemberEmailService then:
|
| - requires a verified primary email;
| - prevents duplicate reminder queue records;
| - renders the existing MembershipActivity email;
| - adds the email to the existing durable email queue.
|
| The email worker remains responsible for actual delivery.
*/
$batchSize =
    isset(
        $argv[1]
    )
    ? (int) $argv[1]
    : 500;

$batchSize =
    max(
        1,
        min(
            1000,
            $batchSize
        )
    );

try {
    /** @var MembershipLifecycleService $membershipLifecycleService */
    $membershipLifecycleService =
        service(
            'membershipLifecycleService'
        );

    $result =
        $membershipLifecycleService
        ->queueExpiryReminders(
            $batchSize
        );

    echo PHP_EOL;

    echo 'Membership expiry reminder complete'
        . PHP_EOL;

    echo '==================================='
        . PHP_EOL;

    echo 'Target date: '
        . (string) (
            $result['target_date']
            ?? ''
        )
        . PHP_EOL;

    echo 'Scanned:     '
        . (int) (
            $result['scanned']
            ?? 0
        )
        . PHP_EOL;

    echo 'Queued:      '
        . (int) (
            $result['queued']
            ?? 0
        )
        . PHP_EOL;

    echo 'Skipped:     '
        . (int) (
            $result['skipped']
            ?? 0
        )
        . PHP_EOL;

    echo 'Failed:      '
        . (int) (
            $result['failed']
            ?? 0
        )
        . PHP_EOL;

    /*
     * A failed member reminder should be visible to cron/server
     * monitoring while successfully queued reminders remain intact.
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
     * Keep top-level operational logging consistent with the existing
     * membership lifecycle script.
     */
    log_message(
        'error',
        'Membership expiry reminder failed: {message}',
        [
            'message' =>
            $exception
                ->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        'Membership expiry reminder failed: '
            . $exception
            ->getMessage()
            . PHP_EOL
    );

    exit(1);
}
