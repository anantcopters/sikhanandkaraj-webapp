<?php

declare(strict_types=1);

use App\Services\Development\DevelopmentCandidateQueryProfilerService;
use CodeIgniter\Boot;
use Config\Paths;

/*
 * Membership-26 PostgreSQL candidate-query profiler.
 *
 * This script exposes SQL/execution-plan information and therefore must never
 * be available through HTTP.
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

if (!defined('ENVIRONMENT')) {
    $environment =
        getenv(
            'CI_ENVIRONMENT'
        );

    if (
        !is_string($environment)
        || trim($environment) === ''
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

$memberId =
    isset($argv[1])
    ? max(
        0,
        (int) $argv[1]
    )
    : 0;

if ($memberId <= 0) {
    fwrite(
        STDERR,
        PHP_EOL
            . 'Usage:'
            . PHP_EOL
            . '  php scripts/profile_candidate_query.php <member-id>'
            . PHP_EOL
            . PHP_EOL
    );

    exit(1);
}

try {
    /** @var DevelopmentCandidateQueryProfilerService $profiler */
    $profiler =
        service(
            'developmentCandidateQueryProfilerService'
        );

    $result =
        $profiler
        ->profileEligibleCandidates(
            $memberId
        );

    $operations =
        $profiler
        ->importantOperations(
            $result['plan']
        );

    fwrite(
        STDOUT,
        PHP_EOL
            . 'Membership-26 Candidate Query Profile'
            . PHP_EOL
            . '====================================='
            . PHP_EOL
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Member: '
            . (
                $result['profileReference'] !== ''
                ? $result['profileReference']
                : '#'
                . $result['memberId']
            )
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Gender: '
            . $result['gender']
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Planning time: '
            . number_format(
                $result['planningTimeMs'],
                3
            )
            . ' ms'
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Execution time: '
            . number_format(
                $result['executionTimeMs'],
                3
            )
            . ' ms'
            . PHP_EOL
            . PHP_EOL
    );

    fwrite(
        STDOUT,
        'Important Plan Operations'
            . PHP_EOL
            . '-------------------------'
            . PHP_EOL
    );

    if ($operations === []) {
        fwrite(
            STDOUT,
            'No notable plan operations were returned.'
                . PHP_EOL
        );
    }

    foreach ($operations as $operation) {
        $indent =
            str_repeat(
                '  ',
                max(
                    0,
                    (int) $operation['depth']
                )
            );

        $description =
            $indent
            . '- '
            . $operation['nodeType'];

        if ($operation['relation'] !== '') {
            $description .=
                ' on '
                . $operation['relation'];
        }

        if ($operation['index'] !== '') {
            $description .=
                ' using '
                . $operation['index'];
        }

        $description .=
            ' | rows='
            . $operation['actualRows']
            . ' | loops='
            . $operation['loops']
            . ' | '
            . number_format(
                $operation['totalTimeMs'],
                3
            )
            . ' ms'
            . ' | hit='
            . $operation['sharedHitBlocks']
            . ' | read='
            . $operation['sharedReadBlocks'];

        fwrite(
            STDOUT,
            $description
                . PHP_EOL
        );
    }

    fwrite(
        STDOUT,
        PHP_EOL
            . 'Compiled Candidate SQL'
            . PHP_EOL
            . '----------------------'
            . PHP_EOL
            . $result['sql']
            . PHP_EOL
            . PHP_EOL
            . 'Profile complete.'
            . PHP_EOL
    );

    exit(0);
} catch (Throwable $exception) {
    log_message(
        'critical',
        'Development candidate query profiler stopped: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    fwrite(
        STDERR,
        PHP_EOL
            . 'Candidate query profiler failed: '
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
