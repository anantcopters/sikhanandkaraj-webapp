<?php

declare(strict_types=1);

use App\Services\Development\DevelopmentProfileLoaderService;
use CodeIgniter\Boot;
use Config\Paths;

/*
 * This operation requires direct filesystem, database and AWS access.
 * It must never be available through Apache or a browser route.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);

    exit(1);
}

$projectRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

if ($projectRoot === false) {
    fwrite(
        STDERR,
        'The project root directory could not be resolved.'
            . PHP_EOL
    );

    exit(1);
}

/*
 * Match the front-controller meaning of FCPATH: the public directory.
 */
$publicPath = realpath(
    $projectRoot . DIRECTORY_SEPARATOR . 'public'
);

if ($publicPath === false) {
    fwrite(
        STDERR,
        'The public directory could not be resolved.'
            . PHP_EOL
    );

    exit(1);
}

define(
    'FCPATH',
    rtrim($publicPath, '\\/')
        . DIRECTORY_SEPARATOR
);

/*
 * The framework bootstrap requires an environment constant before
 * Boot::bootConsole() loads the environment-specific bootstrap file.
 *
 * The loader itself performs a second strict check for both:
 *
 * - ENVIRONMENT === development
 * - APP_DEPLOYMENT === development
 */
if (!defined('ENVIRONMENT')) {
    $environment = getenv('CI_ENVIRONMENT');

    if (
        !is_string($environment)
        || trim($environment) === ''
    ) {
        $environment = 'development';
    }

    define(
        'ENVIRONMENT',
        strtolower(trim($environment))
    );
}

/*
 * Resolve relative paths consistently from the project root.
 */
chdir($projectRoot);

require $projectRoot
    . DIRECTORY_SEPARATOR
    . 'app'
    . DIRECTORY_SEPARATOR
    . 'Config'
    . DIRECTORY_SEPARATOR
    . 'Paths.php';

$paths = new Paths();

require rtrim(
    $paths->systemDirectory,
    '\\/'
) . DIRECTORY_SEPARATOR . 'Boot.php';

/*
 * bootConsole() initializes constants, autoloading, Composer, environment
 * configuration and CodeIgniter services. It does not execute Spark here.
 */
Boot::bootConsole($paths);

try {
    /** @var DevelopmentProfileLoaderService $loader */
    $loader = service(
        'developmentProfileLoaderService'
    );

    $result = $loader->loadAll();

    fwrite(
        STDOUT,
        PHP_EOL
            . 'Development profile import completed.'
            . PHP_EOL
            . 'Batch: '
            . $result['batch']
            . PHP_EOL
            . 'Created: '
            . $result['created']
            . PHP_EOL
            . 'Skipped: '
            . $result['skipped']
            . PHP_EOL
            . 'Failed: '
            . $result['failed']
            . PHP_EOL
    );

    foreach ($result['profiles'] as $profile) {
        fwrite(
            STDOUT,
            sprintf(
                "[%s] %s | %s | Photos: %d%s",
                (string) ($profile['status'] ?? 'UNKNOWN'),
                (string) ($profile['source'] ?? '-'),
                (string) (
                    $profile['profileReference']
                    ?? '-'
                ),
                (int) ($profile['photoCount'] ?? 0),
                PHP_EOL
            )
        );
    }

    if ($result['errors'] !== []) {
        fwrite(
            STDERR,
            PHP_EOL . 'Errors:' . PHP_EOL
        );

        foreach ($result['errors'] as $error) {
            fwrite(
                STDERR,
                sprintf(
                    "- %s: %s%s",
                    (string) ($error['folder'] ?? '-'),
                    (string) (
                        $error['message']
                        ?? 'Unknown error'
                    ),
                    PHP_EOL
                )
            );
        }
    }

    exit($result['failed'] > 0
        ? 1
        : 0);
} catch (\Throwable $exception) {
    log_message(
        'critical',
        'Development profile loader stopped: {message}',
        [
            'message' => $exception->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        'Development profile loader failed: '
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
