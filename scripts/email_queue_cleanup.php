<?php

declare(strict_types=1);

use CodeIgniter\Boot;
use Config\Paths;
use Throwable;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI access only.');
}

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
        getenv('CI_ENVIRONMENT') ?: 'production'
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

try {
    $database->transBegin();

    /*
     * Remove successfully delivered emails after 90 days.
     * Attempt records are deleted automatically through ON DELETE CASCADE.
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

    $sentDeleted = $database->affectedRows();

    /*
     * Retain failed deliveries longer for investigation.
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

    $failedDeleted = $database->affectedRows();

    /*
     * Expired verification tokens no longer serve any purpose.
     * Keep them for 30 additional days for limited diagnostics.
     */
    $database->query(
        <<<'SQL'
            DELETE FROM email_verification_tokens
            WHERE expires_at <
                CURRENT_TIMESTAMP - INTERVAL '30 days'
        SQL
    );

    $tokensDeleted = $database->affectedRows();

    if (!$database->transStatus()) {
        throw new RuntimeException(
            'Email cleanup transaction failed.'
        );
    }

    $database->transCommit();

    echo sprintf(
        "[%s] Cleanup complete. Sent=%d Failed=%d Tokens=%d\n",
        date('Y-m-d H:i:s'),
        $sentDeleted,
        $failedDeleted,
        $tokensDeleted
    );

    exit(0);
} catch (Throwable $exception) {
    $database->transRollback();

    $message = sprintf(
        '[%s] Email cleanup failed: %s',
        date('Y-m-d H:i:s'),
        $exception->getMessage()
    );

    fwrite(STDERR, $message . PHP_EOL);

    log_message(
        'critical',
        'Email cleanup failed: {message}',
        [
            'message' => $exception->getMessage(),
        ]
    );

    exit(1);
}
