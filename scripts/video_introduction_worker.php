<?php

declare(strict_types=1);

use CodeIgniter\Boot;
use Config\Paths;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

define(
    'FCPATH',
    realpath(__DIR__ . '/../public')
        . DIRECTORY_SEPARATOR
);

require FCPATH
    . '../app/Config/Paths.php';

$paths = new Paths();

require rtrim(
    $paths->systemDirectory,
    '\\/ '
)
    . DIRECTORY_SEPARATOR
    . 'Boot.php';

Boot::bootConsole($paths);

$hostName = gethostname();

$workerId =
    ($hostName !== false
        ? $hostName
        : 'video-worker')
    . ':'
    . getmypid();

$processed = 0;

$limit = max(
    1,
    min(
        (int) ($argv[1] ?? 10),
        100
    )
);

/**
 * @var \App\Services\Video\VideoIntroductionProcessingService $service
 */
$service = service(
    'videoIntroductionProcessingService'
);

while (
    $processed < $limit
    && $service->processNext($workerId)
) {
    $processed++;
}

fwrite(
    STDOUT,
    'Processed '
        . $processed
        . " Video Introduction job(s).\n"
);
