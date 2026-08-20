<?php

declare(strict_types=1);

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
| QA and Production should both run CodeIgniter in production mode.
|
| Local development example:
|
| CI_ENVIRONMENT=development php scripts/video_introduction_worker.php
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
| php scripts/video_introduction_worker.php
| php scripts/video_introduction_worker.php 10
|
| The batch limit is bounded to avoid excessive server, FFmpeg and database
| load during a single invocation.
|
*/

$requestedLimit = isset($argv[1])
    ? (int) $argv[1]
    : 10;

$batchLimit = max(
    1,
    min(
        $requestedLimit,
        100
    )
);

$hostName = gethostname();

$workerId = sprintf(
    '%s:%d',
    $hostName !== false
        ? $hostName
        : 'video-worker',
    getmypid()
);

$processed = 0;

try {
    /**
     * @var \App\Services\Video\VideoIntroductionProcessingService $service
     */
    $service = service(
        'videoIntroductionProcessingService'
    );

    while (
        $processed < $batchLimit
        && $service->processNext(
            $workerId
        )
    ) {
        $processed++;
    }

    fwrite(
        STDOUT,
        sprintf(
            "[%s] Processed %d Video Introduction job(s).\n",
            date('Y-m-d H:i:s'),
            $processed
        )
    );

    exit(0);
} catch (Throwable $exception) {
    $message = sprintf(
        '[%s] Video Introduction worker failed: %s',
        date('Y-m-d H:i:s'),
        $exception->getMessage()
    );

    fwrite(
        STDERR,
        $message . PHP_EOL
    );

    log_message(
        'critical',
        'Video Introduction worker failed: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    exit(1);
}
