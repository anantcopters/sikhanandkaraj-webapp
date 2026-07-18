<?php

declare(strict_types=1);

use App\Services\Email\EmailQueueWorker;
use CodeIgniter\Boot;
use Config\Paths;
use Throwable;

/*
|--------------------------------------------------------------------------
| Restrict execution to CLI
|--------------------------------------------------------------------------
|
| This file must never run through Apache or a browser.
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
|
| scripts/email_worker.php
| project root = dirname(__DIR__)
|
*/
$projectRoot = dirname(__DIR__);

define(
    'FCPATH',
    $projectRoot
        . DIRECTORY_SEPARATOR
        . 'public'
        . DIRECTORY_SEPARATOR
);

/*
|--------------------------------------------------------------------------
| Set working directory
|--------------------------------------------------------------------------
*/
chdir($projectRoot);

/*
|--------------------------------------------------------------------------
| Define environment
|--------------------------------------------------------------------------
|
| Cron may not provide CI_ENVIRONMENT. Production is therefore the safe
| default. Locally, you may run:
|
| CI_ENVIRONMENT=development php scripts/email_worker.php
|
*/
if (!defined('ENVIRONMENT')) {
    define(
        'ENVIRONMENT',
        getenv('CI_ENVIRONMENT') ?: 'production'
    );
}

/*
|--------------------------------------------------------------------------
| Load CI4 paths
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

/*
|--------------------------------------------------------------------------
| Boot CodeIgniter for a standalone console script
|--------------------------------------------------------------------------
*/
require $paths->systemDirectory
    . DIRECTORY_SEPARATOR
    . 'Boot.php';

Boot::bootConsole($paths);

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
| Maximum batch size is deliberately restricted to avoid server load.
|
*/
$requestedLimit = isset($argv[1])
    ? (int) $argv[1]
    : 5;

$batchLimit = max(
    1,
    min($requestedLimit, 20)
);

$startedAt = microtime(true);

try {
    $result = (
        new EmailQueueWorker()
    )->process($batchLimit);

    $durationMs = (int) round(
        (microtime(true) - $startedAt) * 1000
    );

    $message = sprintf(
        '[%s] Email worker completed. '
            . 'Reserved=%d Sent=%d Retried=%d Failed=%d Duration=%dms',
        date('Y-m-d H:i:s'),
        $result['reserved'],
        $result['sent'],
        $result['retried'],
        $result['failed'],
        $durationMs
    );

    echo $message . PHP_EOL;

    if ($result['reserved'] > 0) {
        log_message(
            'info',
            'Email worker completed. Reserved={reserved}, '
                . 'sent={sent}, retried={retried}, '
                . 'failed={failed}, durationMs={durationMs}',
            [
                'reserved' => $result['reserved'],
                'sent' => $result['sent'],
                'retried' => $result['retried'],
                'failed' => $result['failed'],
                'durationMs' => $durationMs,
            ]
        );
    }

    exit(0);
} catch (Throwable $exception) {
    $message = sprintf(
        '[%s] Email worker failed: %s',
        date('Y-m-d H:i:s'),
        $exception->getMessage()
    );

    fwrite(
        STDERR,
        $message . PHP_EOL
    );

    log_message(
        'critical',
        'Email queue worker failed: {message}',
        [
            'message' => $exception->getMessage(),
        ]
    );

    exit(1);
}
