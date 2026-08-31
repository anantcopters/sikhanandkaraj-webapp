<?php

declare(strict_types=1);

/**
 * ==========================================================================
 * Engagement Digest Processor
 * ==========================================================================
 *
 * Queues DAILY or WEEKLY Engagement digest emails from durable
 * communication events.
 *
 * Usage:
 *
 * php scripts/engagement_digest.php DAILY
 * php scripts/engagement_digest.php DAILY 100
 *
 * php scripts/engagement_digest.php WEEKLY
 * php scripts/engagement_digest.php WEEKLY 100
 *
 * This script does not send SMTP traffic directly.
 *
 * It only converts eligible Engagement communication events into the
 * existing durable email queue.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(
        STDERR,
        "This script may only be executed from CLI.\n"
    );

    exit(1);
}

define(
    'FCPATH',
    realpath(
        __DIR__
            . '/../public'
    )
        . DIRECTORY_SEPARATOR
);

chdir(
    FCPATH
);

require FCPATH
    . '../app/Config/Paths.php';

$paths =
    new Config\Paths();

require $paths->systemDirectory
    . '/Boot.php';

if (
    getenv(
        'CI_ENVIRONMENT'
    ) === false
) {
    putenv(
        'CI_ENVIRONMENT=production'
    );
}

CodeIgniter\Boot::bootConsole(
    $paths
);

$frequency =
    isset(
        $argv[1]
    )
    ? mb_strtoupper(
        trim(
            (string) $argv[1]
        )
    )
    : '';

if (
    !in_array(
        $frequency,
        [
            'DAILY',
            'WEEKLY',
        ],
        true
    )
) {
    fwrite(
        STDERR,
        "Frequency must be DAILY or WEEKLY.\n"
    );

    exit(1);
}

$batchSize =
    isset(
        $argv[2]
    )
    ? (int) $argv[2]
    : 100;

$batchSize =
    max(
        1,
        min(
            500,
            $batchSize
        )
    );

try {
    $result =
        service(
            'engagementDigestService'
        )->processDue(
            $frequency,
            $batchSize
        );

    fwrite(
        STDOUT,
        "Engagement digest complete\n"
    );

    fwrite(
        STDOUT,
        "==========================\n"
    );

    fwrite(
        STDOUT,
        'Frequency:  '
            . $frequency
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Recipients: '
            . $result['recipients']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Queued:     '
            . $result['queued']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Events:     '
            . $result['events']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Skipped:    '
            . $result['skipped']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Failed:     '
            . $result['failed']
            . PHP_EOL
    );

    exit($result['failed'] > 0
        ? 1
        : 0);
} catch (Throwable $exception) {
    log_message(
        'critical',
        'Engagement digest processor failed. '
            . 'Frequency: {frequency}; '
            . 'Error: {error}',
        [
            'frequency' =>
            $frequency,

            'error' =>
            $exception
                ->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        'Engagement digest processor failed: '
            . $exception
            ->getMessage()
            . PHP_EOL
    );

    exit(1);
}
