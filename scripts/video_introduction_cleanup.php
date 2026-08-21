<?php

declare(strict_types=1);

use CodeIgniter\Boot;
use Config\Paths;

/*
|--------------------------------------------------------------------------
| CLI restriction
|--------------------------------------------------------------------------
|
| This cleanup script must never run through Apache or a browser.
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
| CI_ENVIRONMENT=development php scripts/video_introduction_cleanup.php
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
| Purge expired Video Introduction assets
|--------------------------------------------------------------------------
*/

try {
    /**
     * @var \App\Services\Video\MemberVideoIntroductionService $service
     */
    $service = service(
        'memberVideoIntroductionService'
    );

    $purgedCount = $service->purgeExpiredAssets(
        100
    );

    fwrite(
        STDOUT,
        sprintf(
            "[%s] Purged assets for %d Video Introduction record(s).\n",
            date('Y-m-d H:i:s'),
            $purgedCount
        )
    );

    exit(0);
} catch (Throwable $exception) {
    $message = sprintf(
        '[%s] Video Introduction cleanup failed: %s',
        date('Y-m-d H:i:s'),
        $exception->getMessage()
    );

    fwrite(
        STDERR,
        $message . PHP_EOL
    );

    log_message(
        'critical',
        'Video Introduction cleanup failed: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    exit(1);
}
