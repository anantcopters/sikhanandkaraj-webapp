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

/**
 * @var \App\Services\Video\MemberVideoIntroductionService $service
 */
$service = service(
    'memberVideoIntroductionService'
);

$count = $service->purgeExpiredAssets(
    100
);

fwrite(
    STDOUT,
    'Purged assets for '
        . $count
        . " Video Introduction record(s).\n"
);
