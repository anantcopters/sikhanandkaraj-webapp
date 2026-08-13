<?php

declare(strict_types=1);

use App\Support\InfrastructureErrorContext;
use CodeIgniter\Boot;
use Config\Paths;

/*
|--------------------------------------------------------------------------
| CLI restriction
|--------------------------------------------------------------------------
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

$database = db_connect();

$startedAt = microtime(true);

try {
    $database->transBegin();

    /*
     * Delivered emails are retained for 90 days.
     *
     * Related attempt records should be removed through the existing
     * ON DELETE CASCADE relationship.
     */
    $database->query(
        <<<'SQL'
            DELETE FROM email_queue
            WHERE status = 'SENT'
              AND sent_at IS NOT NULL
              AND sent_at <
                  CURRENT_TIMESTAMP - INTERVAL '90 days'
        SQL
    );

    $sentDeleted =
        $database->affectedRows();

    /*
     * Failed deliveries are retained for 180 days for investigation.
     */
    $database->query(
        <<<'SQL'
            DELETE FROM email_queue
            WHERE status = 'FAILED'
              AND failed_at IS NOT NULL
              AND failed_at <
                  CURRENT_TIMESTAMP - INTERVAL '180 days'
        SQL
    );

    $failedDeleted =
        $database->affectedRows();

    /*
     * Expired verification tokens are retained for 30 additional days.
     */
    $database->query(
        <<<'SQL'
            DELETE FROM email_verification_tokens
            WHERE expires_at <
                CURRENT_TIMESTAMP - INTERVAL '30 days'
        SQL
    );

    $tokensDeleted =
        $database->affectedRows();

    if (!$database->transStatus()) {
        throw new RuntimeException(
            'Email cleanup transaction failed.'
        );
    }

    $database->transCommit();

    $durationMs = (int) round(
        (
            microtime(true)
            - $startedAt
        ) * 1000
    );

    fwrite(
        STDOUT,
        sprintf(
            '[%s] Email cleanup completed. '
                . 'Sent=%d Failed=%d Tokens=%d '
                . 'Duration=%dms%s',
            date('Y-m-d H:i:s'),
            $sentDeleted,
            $failedDeleted,
            $tokensDeleted,
            $durationMs,
            PHP_EOL
        )
    );

    /*
     * Successful cleanup details remain informational file logs.
     */
    log_message(
        'info',
        'Email queue cleanup completed. '
            . 'Sent={sent}; '
            . 'failed={failed}; '
            . 'tokens={tokens}; '
            . 'durationMs={durationMs}.',
        [
            'sent' =>
            $sentDeleted,

            'failed' =>
            $failedDeleted,

            'tokens' =>
            $tokensDeleted,

            'durationMs' =>
            $durationMs,
        ]
    );

    exit(0);
} catch (Throwable $exception) {
    if (
        $database->transStatus() === false
        || $database->transDepth > 0
    ) {
        /*
         * Rollback is protected because cleanup error handling must not create
         * another fatal exception.
         */
        try {
            $database->transRollback();
        } catch (Throwable) {
            // The original failure remains the authoritative exception.
        }
    }

    $durationMs = (int) round(
        (
            microtime(true)
            - $startedAt
        ) * 1000
    );

    fwrite(
        STDERR,
        sprintf(
            '[%s] Email cleanup failed: %s%s',
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            PHP_EOL
        )
    );

    service(
        'applicationErrorLogger'
    )->exception(
        $exception,
        'critical',
        InfrastructureErrorContext::forOperation(
            operation: 'email_queue_cleanup',

            component: basename(__FILE__),

            method: 'main',

            additionalContext: [
                'duration_ms' =>
                $durationMs,

                'execution_source' =>
                'CRON',
            ]
        )
    );

    exit(1);
}
