<?php

declare(strict_types=1);

use App\Services\Email\EmailQueueWorker;
use App\Support\InfrastructureErrorContext;
use CodeIgniter\Boot;
use Config\Paths;

/*
|--------------------------------------------------------------------------
| CLI restriction
|--------------------------------------------------------------------------
|
| This worker must never run through Apache or a browser.
|
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);

    exit('CLI access only.');
}

/*
|--------------------------------------------------------------------------
| Resolve project directories
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

/*
|--------------------------------------------------------------------------
| Environment
|--------------------------------------------------------------------------
|
| Cron may not provide CI_ENVIRONMENT. Production is the safe fallback.
|
| Local example:
|
| CI_ENVIRONMENT=development php scripts/email_worker.php
|
*/

if (!defined('ENVIRONMENT')) {
    define(
        'ENVIRONMENT',
        getenv('CI_ENVIRONMENT')
            ?: 'production'
    );
}

/*
|--------------------------------------------------------------------------
| Boot CodeIgniter
|--------------------------------------------------------------------------
*/

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
| Worker configuration
|--------------------------------------------------------------------------
|
| Usage:
|
| php scripts/email_worker.php
| php scripts/email_worker.php 5
|
| The batch limit is bounded to reduce SMTP and database load.
|
*/

$requestedLimit = isset($argv[1])
    ? (int) $argv[1]
    : 5;

$batchLimit = max(
    1,
    min(
        $requestedLimit,
        20
    )
);

$workerName = sprintf(
    '%s:%d',
    gethostname() !== false
        ? gethostname()
        : 'unknown',
    getmypid()
);

$workerName = mb_substr(
    $workerName,
    0,
    100
);

$startedAt = microtime(true);

try {
    $result = (
        new EmailQueueWorker()
    )->process(
        $batchLimit,
        $workerName
    );

    $durationMs = (int) round(
        (
            microtime(true)
            - $startedAt
        ) * 1000
    );

    fwrite(
        STDOUT,
        sprintf(
            '[%s] Email worker completed. '
                . 'Reserved=%d Sent=%d Retried=%d '
                . 'Failed=%d Duration=%dms%s',
            date('Y-m-d H:i:s'),
            $result['reserved'],
            $result['sent'],
            $result['retried'],
            $result['failed'],
            $durationMs,
            PHP_EOL
        )
    );

    /*
     * Completion is operational information, not an application error.
     * It remains in the standard file log only because the database handler
     * processes warning and higher levels.
     */
    if ($result['reserved'] > 0) {
        log_message(
            'info',
            'Email worker completed. '
                . 'Reserved={reserved}; '
                . 'sent={sent}; '
                . 'retried={retried}; '
                . 'failed={failed}; '
                . 'durationMs={durationMs}.',
            [
                'reserved' =>
                $result['reserved'],

                'sent' =>
                $result['sent'],

                'retried' =>
                $result['retried'],

                'failed' =>
                $result['failed'],

                'durationMs' =>
                $durationMs,
            ]
        );
    }

    /*
     * A completed worker run may contain terminal message failures, but those
     * failures are already logged by EmailQueueService::markFailed().
     */
    exit(0);
} catch (Throwable $exception) {
    $durationMs = (int) round(
        (
            microtime(true)
            - $startedAt
        ) * 1000
    );

    fwrite(
        STDERR,
        sprintf(
            '[%s] Email worker failed: %s%s',
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            PHP_EOL
        )
    );

    /*
     * The outer script owns errors that prevent the worker batch itself from
     * running, such as reservation, boot, database or queue-state failures.
     */
    service(
        'applicationErrorLogger'
    )->exception(
        $exception,
        'critical',
        InfrastructureErrorContext::forOperation(
            operation: 'email_queue_worker',

            component: basename(__FILE__),

            method: 'main',

            additionalContext: [
                'worker_name' =>
                $workerName,

                'batch_limit' =>
                $batchLimit,

                'duration_ms' =>
                $durationMs,

                'execution_source' =>
                'CRON',
            ]
        )
    );

    exit(1);
}
