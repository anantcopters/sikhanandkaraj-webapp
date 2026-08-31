<?php

declare(strict_types=1);

/**
 * ==========================================================================
 * Communication Event Dispatcher
 * ==========================================================================
 *
 * Processes durable channel-independent communication events.
 *
 * Usage:
 *
 * php scripts/communication_dispatcher.php
 * php scripts/communication_dispatcher.php 100
 *
 * The dispatcher does not send provider traffic directly.
 *
 * Email/SMS/WhatsApp delivery remains the responsibility of the
 * corresponding channel queues/workers.
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

$batchSize =
    isset(
        $argv[1]
    )
    ? (int) $argv[1]
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
            'communicationDispatcherService'
        )->processPending(
            $batchSize
        );

    fwrite(
        STDOUT,
        "Communication dispatcher complete\n"
    );

    fwrite(
        STDOUT,
        "===============================\n"
    );

    fwrite(
        STDOUT,
        'Reserved:  '
            . $result['reserved']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Processed: '
            . $result['processed']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Failed:    '
            . $result['failed']
            . PHP_EOL
    );

    exit($result['failed'] > 0
        ? 1
        : 0);
} catch (Throwable $exception) {
    log_message(
        'critical',
        'Communication dispatcher failed. '
            . 'Error: {error}',
        [
            'error' =>
            $exception
                ->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        'Communication dispatcher failed: '
            . $exception
            ->getMessage()
            . PHP_EOL
    );

    exit(1);
}
