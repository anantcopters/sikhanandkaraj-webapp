<?php

declare(strict_types=1);

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

Boot::bootConsole($paths);

$database = db_connect();

/*
|--------------------------------------------------------------------------
| Retention settings
|--------------------------------------------------------------------------
|
| Technical HTTP request logs generate higher volume and are retained for
| 90 days.
|
| Administrator business audit logs are retained for two years.
|
*/
$httpRetentionDays = 90;
$adminAuditRetentionYears = 2;

try {
    $database->transBegin();

    /*
     * Delete technical request/response logs older than 90 days.
     */
    $database->query(
        <<<'SQL'
            DELETE FROM http_request_logs
            WHERE occurred_at <
                CURRENT_TIMESTAMP - INTERVAL '90 days'
        SQL
    );

    $httpRequestLogsDeleted =
        $database->affectedRows();

    /*
     * Delete administrator business audit history older than two years.
     */
    $database->query(
        <<<'SQL'
            DELETE FROM admin_audit_logs
            WHERE occurred_at <
                CURRENT_TIMESTAMP - INTERVAL '2 years'
        SQL
    );

    $adminAuditLogsDeleted =
        $database->affectedRows();

    if (!$database->transStatus()) {
        throw new RuntimeException(
            'Log-retention cleanup transaction failed.'
        );
    }

    $database->transCommit();

    $message = sprintf(
        '[%s] Log cleanup complete. '
            . 'HttpRequestLogs=%d AdminAuditLogs=%d '
            . 'HttpRetentionDays=%d AdminAuditRetentionYears=%d',
        date('Y-m-d H:i:s'),
        $httpRequestLogsDeleted,
        $adminAuditLogsDeleted,
        $httpRetentionDays,
        $adminAuditRetentionYears
    );

    echo $message . PHP_EOL;

    /*
     * Write to the normal CI log only when records were actually deleted.
     */
    if (
        $httpRequestLogsDeleted > 0
        || $adminAuditLogsDeleted > 0
    ) {
        log_message(
            'info',
            'Log cleanup completed. '
                . 'HttpRequestLogs={httpDeleted}, '
                . 'AdminAuditLogs={auditDeleted}.',
            [
                'httpDeleted' =>
                $httpRequestLogsDeleted,

                'auditDeleted' =>
                $adminAuditLogsDeleted,
            ]
        );
    }

    exit(0);
} catch (Throwable $exception) {
    $database->transRollback();

    $message = sprintf(
        '[%s] Log cleanup failed: %s',
        date('Y-m-d H:i:s'),
        $exception->getMessage()
    );

    fwrite(
        STDERR,
        $message . PHP_EOL
    );

    log_message(
        'critical',
        'Log-retention cleanup failed: {message}',
        [
            'message' =>
            $exception->getMessage(),
        ]
    );

    exit(1);
}
