<?php

declare(strict_types=1);

use App\Services\Development\DevelopmentSearchProfilerService;
use CodeIgniter\Boot;
use Config\Paths;

/*
 * Membership-24 Search profiler.
 *
 * This operation exposes SQL and database timing information.
 *
 * It must NEVER be accessible through Apache or a browser route.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(
        404
    );

    exit(1);
}

$projectRoot =
    realpath(
        __DIR__
            . DIRECTORY_SEPARATOR
            . '..'
    );

if ($projectRoot === false) {
    fwrite(
        STDERR,
        'The project root directory could not be resolved.'
            . PHP_EOL
    );

    exit(1);
}

$publicPath =
    realpath(
        $projectRoot
            . DIRECTORY_SEPARATOR
            . 'public'
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
    rtrim(
        $publicPath,
        '\\/'
    )
        . DIRECTORY_SEPARATOR
);

/*
 * Follow the same console bootstrap approach used by
 * load_development_profiles.php.
 */
if (!defined('ENVIRONMENT')) {
    $environment =
        getenv(
            'CI_ENVIRONMENT'
        );

    if (
        !is_string(
            $environment
        )
        || trim(
            $environment
        ) === ''
    ) {
        $environment =
            'development';
    }

    define(
        'ENVIRONMENT',
        strtolower(
            trim(
                $environment
            )
        )
    );
}

chdir(
    $projectRoot
);

require $projectRoot
    . DIRECTORY_SEPARATOR
    . 'app'
    . DIRECTORY_SEPARATOR
    . 'Config'
    . DIRECTORY_SEPARATOR
    . 'Paths.php';

$paths =
    new Paths();

require rtrim(
    $paths->systemDirectory,
    '\\/'
)
    . DIRECTORY_SEPARATOR
    . 'Boot.php';

Boot::bootConsole(
    $paths
);

/*
 * Usage:
 *
 * php scripts/profile_member_search.php <member-id> [basic|advanced]
 *
 * Examples:
 *
 * php scripts/profile_member_search.php 25
 *
 * php scripts/profile_member_search.php 25 basic
 *
 * php scripts/profile_member_search.php 25 advanced
 */

$memberId =
    isset(
        $argv[1]
    )
    ? max(
        0,
        (int) $argv[1]
    )
    : 0;

$mode =
    strtolower(
        trim(
            (string) (
                $argv[2]
                ?? 'basic'
            )
        )
    );

if ($memberId <= 0) {
    fwrite(
        STDERR,
        PHP_EOL
            . 'Usage:'
            . PHP_EOL
            . '  php scripts/profile_member_search.php <member-id> [basic|advanced]'
            . PHP_EOL
            . PHP_EOL
    );

    exit(1);
}

if (
    !in_array(
        $mode,
        [
            'basic',
            'advanced',
        ],
        true
    )
) {
    fwrite(
        STDERR,
        'Search mode must be basic or advanced.'
            . PHP_EOL
    );

    exit(1);
}

try {
    /** @var DevelopmentSearchProfilerService $profiler */
    $profiler =
        service(
            'developmentSearchProfilerService'
        );

    /*
     * Start with the real default Match Score Search.
     *
     * No artificial Search filters are introduced by the diagnostic script.
     *
     * Once this baseline is established we can profile individual filters
     * separately when required.
     */
    $result =
        $profiler->profile(
            $memberId,
            [
                'mode' =>
                $mode,

                'sort' =>
                'match',

                'page' =>
                1,
            ]
        );

    fwrite(
        STDOUT,
        PHP_EOL
            . 'Membership-24 Search Profile'
            . PHP_EOL
            . '============================'
            . PHP_EOL
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Member: '
            . (
                $result['profileReference']
                !== ''
                ? $result['profileReference']
                : '#'
                . $result['memberId']
            )
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Mode: '
            . strtoupper(
                $result['mode']
            )
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Sort: '
            . $result['sort']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Results returned: '
            . $result['resultCount']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Total matching: '
            . $result['total']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Page: '
            . $result['page']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Total execution: '
            . number_format(
                $result['elapsedMs'],
                3
            )
            . ' ms'
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        PHP_EOL
            . 'Profile complete.'
            . PHP_EOL
    );

    foreach (
        $result['queries']
        as $query
    ) {
        fwrite(
            STDOUT,
            PHP_EOL
                . '#'
                . $query['number']
                . ' | '
                . number_format(
                    $query['elapsedMs'],
                    3
                )
                . ' ms'
                . PHP_EOL
                . $query['sql']
                . PHP_EOL
        );
    }

    fwrite(
        STDOUT,
        PHP_EOL
            . 'Profile complete.'
            . PHP_EOL
    );

    exit(0);
} catch (\Throwable $exception) {
    log_message(
        'critical',
        'Development Search profiler stopped: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        PHP_EOL
            . 'Search profiler failed: '
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
