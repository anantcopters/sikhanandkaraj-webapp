<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Defines database tables that may be cleaned by the maintenance script.
 *
 * Security:
 * - Only entries registered here may be executed.
 * - The CLI script must never accept raw table names, columns or SQL.
 * - Conditions remain application-controlled.
 */
final class TableCleanup extends BaseConfig
{
    /**
     * Registered cleanup jobs.
     *
     * Configuration:
     *
     * table:
     *     Database table to clean.
     *
     * timestampColumn:
     *     Column used to determine record age.
     *
     * retentionDays:
     *     Number of days records are retained.
     *
     * conditions:
     *     Additional equality or NULL conditions.
     *
     * batchSize:
     *     Maximum records deleted in one DELETE statement.
     *
     * @var array<string, array{
     *     table: string,
     *     timestampColumn: string,
     *     retentionDays: int,
     *     conditions: list<array{
     *         column: string,
     *         operator: string,
     *         value?: scalar|null
     *     }>,
     *     batchSize: int
     * }>
     */
    public array $jobs = [
        'read-notifications' => [
            'table' => 'member_notifications',

            /*
             * Retention starts when the member reads the notification,
             * rather than when the notification was created.
             */
            'timestampColumn' => 'read_at',

            'retentionDays' => 30,

            'conditions' => [
                [
                    'column'   => 'read_at',
                    'operator' => 'IS NOT NULL',
                ],
            ],

            'batchSize' => 1000,
        ],

        /*
         * Add future cleanup jobs here.
         *
         * Example:
         *
         * 'expired-otp' => [
         *     'table' => 'otp_requests',
         *     'timestampColumn' => 'expires_at',
         *     'retentionDays' => 7,
         *     'conditions' => [],
         *     'batchSize' => 1000,
         * ],
         */
    ];
}
