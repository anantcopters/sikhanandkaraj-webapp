<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuration for database-backed application error logging.
 *
 * File logging remains enabled separately through Config\Logger.
 */
final class ErrorLogging extends BaseConfig
{
    /**
     * Enable or disable database error logging.
     *
     * This can be disabled immediately through the environment file without
     * changing application code.
     */
    public bool $databaseEnabled;

    /**
     * Maximum error-message length persisted to PostgreSQL.
     */
    public int $messageMaxLength;

    /**
     * Maximum encoded JSON context size.
     */
    public int $contextMaxLength;

    /**
     * Maximum request URI length.
     */
    public int $requestUriMaxLength;

    /**
     * Error records are retained for this number of days.
     */
    public int $retentionDays;

    public function __construct()
    {
        parent::__construct();

        $this->databaseEnabled = filter_var(
            env(
                'ERROR_LOG_DATABASE_ENABLED',
                true
            ),
            FILTER_VALIDATE_BOOL
        );

        $this->messageMaxLength = max(
            1000,
            min(
                32000,
                (int) env(
                    'ERROR_LOG_MESSAGE_MAX_LENGTH',
                    16000
                )
            )
        );

        $this->contextMaxLength = max(
            1000,
            min(
                32000,
                (int) env(
                    'ERROR_LOG_CONTEXT_MAX_LENGTH',
                    16000
                )
            )
        );

        $this->requestUriMaxLength = max(
            500,
            min(
                8000,
                (int) env(
                    'ERROR_LOG_REQUEST_URI_MAX_LENGTH',
                    4000
                )
            )
        );

        $this->retentionDays = max(
            7,
            min(
                365,
                (int) env(
                    'ERROR_LOG_RETENTION_DAYS',
                    90
                )
            )
        );
    }
}
