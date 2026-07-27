<?php

declare(strict_types=1);

use App\Services\Maintenance\TableCleanupResult;
use App\Services\Maintenance\TableCleanupService;
use CodeIgniter\Boot;
use Config\Paths;

/*
|--------------------------------------------------------------------------
| CLI restriction
|--------------------------------------------------------------------------
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI access only.');
}

/*
|--------------------------------------------------------------------------
| Resolve and boot CodeIgniter
|--------------------------------------------------------------------------
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

if (! defined('ENVIRONMENT')) {
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

Boot::bootConsole($paths);

/**
 * Runs configured database cleanup jobs.
 *
 * Usage:
 *
 * php scripts/cleanup_tables.php
 * php scripts/cleanup_tables.php all
 * php scripts/cleanup_tables.php read-notifications
 * php scripts/cleanup_tables.php list
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/*
 * --------------------------------------------------------------------------
 * Existing project bootstrap
 * --------------------------------------------------------------------------
 *
 * Copy the bootstrap block used by the current scripts in this repository.
 * It must load the CodeIgniter environment, autoloader, .env configuration
 * and service container before this point.
 */

$requestedJob = strtolower(
    trim(
        (string) ($argv[1] ?? 'all')
    )
);

try {
    /** @var TableCleanupService $cleanupService */
    $cleanupService = service(
        'tableCleanupService'
    );

    if ($requestedJob === 'list') {
        fwrite(
            STDOUT,
            'Registered cleanup jobs:'
                . PHP_EOL
        );

        foreach (
            $cleanupService->getRegisteredJobs()
            as $jobName
        ) {
            fwrite(
                STDOUT,
                sprintf(
                    ' - %s%s',
                    $jobName,
                    PHP_EOL
                )
            );
        }

        exit(0);
    }

    $results = $requestedJob === 'all'
        ? $cleanupService->runAll()
        : [
            $cleanupService->run(
                $requestedJob
            ),
        ];

    $hasFailure = false;

    foreach ($results as $result) {
        if (! $result instanceof TableCleanupResult) {
            $hasFailure = true;

            fwrite(
                STDERR,
                '[FAILED] Invalid cleanup result.'
                    . PHP_EOL
            );

            continue;
        }

        if (! $result->successful) {
            $hasFailure = true;

            fwrite(
                STDERR,
                sprintf(
                    '[FAILED] %s: %s%s',
                    $result->jobName,
                    $result->errorMessage
                        ?? 'Unknown error.',
                    PHP_EOL
                )
            );

            continue;
        }

        fwrite(
            STDOUT,
            sprintf(
                '[SUCCESS] %s: %d row(s) deleted.%s',
                $result->jobName,
                $result->deletedCount,
                PHP_EOL
            )
        );
    }

    exit($hasFailure ? 1 : 0);
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            '[CRITICAL] Cleanup script failed: %s%s',
            $exception->getMessage(),
            PHP_EOL
        )
    );

    log_message(
        'critical',
        'Table cleanup script failed: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    exit(1);
}
