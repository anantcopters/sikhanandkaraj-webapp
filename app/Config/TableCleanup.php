<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Defines database tables that may be cleaned by the maintenance script.
 *
 * Security:
 * - only entries registered here may be executed;
 * - the CLI script never accepts raw table names, columns or SQL;
 * - all conditions remain application-controlled.
 */
final class TableCleanup extends BaseConfig
{
    /**
     * Registered cleanup jobs.
     *
     * @var array<string, array{
     *     table:string,
     *     timestampColumn:string,
     *     retentionDays:int,
     *     conditions:list<array{
     *         column:string,
     *         operator:string,
     *         value?:scalar|null
     *     }>,
     *     batchSize:int
     * }>
     */
    public array $jobs;

    public function __construct()
    {
        parent::__construct();

        $errorLogging =
            config(ErrorLogging::class);

        $this->jobs = [
            'read-notifications' => [
                'table' =>
                'member_notifications',

                /*
                 * Retention starts when the member reads the notification,
                 * rather than when the notification was created.
                 */
                'timestampColumn' =>
                'read_at',

                'retentionDays' =>
                30,

                'conditions' => [
                    [
                        'column' =>
                        'read_at',

                        'operator' =>
                        'IS NOT NULL',
                    ],
                ],

                'batchSize' =>
                1000,
            ],

            /*
             * Application errors are retained for the configured period and
             * removed in bounded batches by TableCleanupService.
             */
            'application-error-logs' => [
                'table' =>
                'application_error_logs',

                'timestampColumn' =>
                'created_at',

                'retentionDays' =>
                $errorLogging
                    ->retentionDays,

                'conditions' =>
                [],

                'batchSize' =>
                1000,
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
}
