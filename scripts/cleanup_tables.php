<?php

declare(strict_types=1);

use App\Services\Maintenance\FileCleanupResult;
use App\Services\Maintenance\FileCleanupService;
use App\Services\Maintenance\TableCleanupResult;
use App\Services\Maintenance\TableCleanupService;
use CodeIgniter\Boot;
use Config\Paths;
use Throwable;

/*
|--------------------------------------------------------------------------
| CLI restriction
|--------------------------------------------------------------------------
|
| This maintenance script must never be executed through the web server.
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

/**
 * Run configured maintenance cleanup jobs.
 *
 * Usage:
 *
 * php scripts/cleanup_tables.php
 * php scripts/cleanup_tables.php all
 * php scripts/cleanup_tables.php list
 * php scripts/cleanup_tables.php table:read-notifications
 * php scripts/cleanup_tables.php file:prelaunch-approved-photos
 *
 * For backward compatibility, an unprefixed name is treated as a table job:
 *
 * php scripts/cleanup_tables.php read-notifications
 */
$requestedJob = strtolower(
    trim(
        (string) (
            $argv[1]
            ?? 'all'
        )
    )
);

try {
    /** @var TableCleanupService $tableCleanupService */
    $tableCleanupService = service(
        'tableCleanupService'
    );

    /** @var FileCleanupService $fileCleanupService */
    $fileCleanupService = service(
        'fileCleanupService'
    );

    if ($requestedJob === 'list') {
        fwrite(
            STDOUT,
            'Registered table cleanup jobs:'
                . PHP_EOL
        );

        foreach (
            $tableCleanupService
                ->getRegisteredJobs()
            as $jobName
        ) {
            fwrite(
                STDOUT,
                sprintf(
                    ' - table:%s%s',
                    $jobName,
                    PHP_EOL
                )
            );
        }

        fwrite(
            STDOUT,
            'Registered file cleanup jobs:'
                . PHP_EOL
        );

        foreach (
            $fileCleanupService
                ->getRegisteredJobs()
            as $jobName
        ) {
            fwrite(
                STDOUT,
                sprintf(
                    ' - file:%s%s',
                    $jobName,
                    PHP_EOL
                )
            );
        }

        exit(0);
    }

    $tableResults = [];
    $fileResults = [];

    if ($requestedJob === 'all') {
        $tableResults =
            $tableCleanupService->runAll();

        $fileResults =
            $fileCleanupService->runAll();
    } elseif (
        str_starts_with(
            $requestedJob,
            'file:'
        )
    ) {
        $fileJobName = trim(
            substr(
                $requestedJob,
                strlen('file:')
            )
        );

        $fileResults = [
            $fileCleanupService->run(
                $fileJobName
            ),
        ];
    } else {
        /*
         * Support both:
         *
         * table:read-notifications
         * read-notifications
         */
        $tableJobName = str_starts_with(
            $requestedJob,
            'table:'
        )
            ? trim(
                substr(
                    $requestedJob,
                    strlen('table:')
                )
            )
            : $requestedJob;

        $tableResults = [
            $tableCleanupService->run(
                $tableJobName
            ),
        ];
    }

    $hasFailure = false;

    foreach ($tableResults as $result) {
        if (
            !$result
                instanceof TableCleanupResult
        ) {
            $hasFailure = true;

            fwrite(
                STDERR,
                '[FAILED] Invalid table cleanup result.'
                    . PHP_EOL
            );

            continue;
        }

        if (!$result->successful) {
            $hasFailure = true;

            fwrite(
                STDERR,
                sprintf(
                    '[FAILED] table:%s: %s%s',
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
                '[SUCCESS] table:%s: '
                    . '%d row(s) deleted.%s',
                $result->jobName,
                $result->deletedCount,
                PHP_EOL
            )
        );
    }

    foreach ($fileResults as $result) {
        if (
            !$result
                instanceof FileCleanupResult
        ) {
            $hasFailure = true;

            fwrite(
                STDERR,
                '[FAILED] Invalid file cleanup result.'
                    . PHP_EOL
            );

            continue;
        }

        if (
            !$result->successful
            || $result->failedProfiles > 0
        ) {
            $hasFailure = true;

            fwrite(
                STDERR,
                sprintf(
                    '[FAILED] file:%s: %s '
                        . 'Profiles processed: %d; '
                        . 'files deleted: %d; '
                        . 'directories deleted: %d; '
                        . 'profile failures: %d.%s',
                    $result->jobName,
                    $result->errorMessage
                        ?? 'One or more profile folders '
                        . 'could not be cleaned.',
                    $result->processedProfiles,
                    $result->deletedFiles,
                    $result->deletedDirectories,
                    $result->failedProfiles,
                    PHP_EOL
                )
            );

            continue;
        }

        fwrite(
            STDOUT,
            sprintf(
                '[SUCCESS] file:%s: '
                    . '%d profile(s) processed; '
                    . '%d file(s) deleted; '
                    . '%d directorie(s) deleted.%s',
                $result->jobName,
                $result->processedProfiles,
                $result->deletedFiles,
                $result->deletedDirectories,
                PHP_EOL
            )
        );
    }

    exit($hasFailure
        ? 1
        : 0);
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            '[CRITICAL] Maintenance cleanup failed: %s%s',
            $exception->getMessage(),
            PHP_EOL
        )
    );

    service(
        'applicationErrorLogger'
    )->exception(
        $exception,
        'critical',
        [
            'operation' =>
            'maintenance_cleanup_script',

            'component' =>
            basename(__FILE__),

            'method' =>
            'main',

            'requested_job' =>
            $requestedJob,

            'execution_source' =>
            'CRON',
        ]
    );

    exit(1);
}
