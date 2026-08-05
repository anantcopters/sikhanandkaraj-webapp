<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use CodeIgniter\Database\BaseConnection;
use App\Support\InfrastructureErrorContext;
use Config\Database;
use Config\TableCleanup;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;

/**
 * Executes explicitly registered table-cleanup jobs.
 *
 * This service does not accept arbitrary table names. It receives a job name
 * and resolves the corresponding table, columns and conditions from the
 * trusted TableCleanup configuration.
 */
final class TableCleanupService
{
    /**
     * Supported condition operators.
     *
     * Keep this intentionally restrictive. Add another operator only when
     * there is a real cleanup requirement.
     *
     * @var list<string>
     */
    private const ALLOWED_OPERATORS = [
        '=',
        '!=',
        '<>',
        'IS NULL',
        'IS NOT NULL',
    ];

    private BaseConnection $database;

    private TableCleanup $configuration;

    public function __construct(
        ?BaseConnection $database = null,
        ?TableCleanup $configuration = null
    ) {
        $this->database = $database
            ?? Database::connect();

        $this->configuration = $configuration
            ?? config(TableCleanup::class);
    }

    /**
     * Return the names of all registered cleanup jobs.
     *
     * @return list<string>
     */
    public function getRegisteredJobs(): array
    {
        return array_keys(
            $this->configuration->jobs
        );
    }

    /**
     * Execute all registered cleanup jobs.
     *
     * One job failure does not prevent the remaining jobs from executing.
     *
     * @return list<TableCleanupResult>
     */
    public function runAll(): array
    {
        $results = [];

        foreach ($this->getRegisteredJobs() as $jobName) {
            $results[] = $this->run($jobName);
        }

        return $results;
    }

    /**
     * Execute one registered cleanup job.
     */
    public function run(
        string $jobName
    ): TableCleanupResult {
        $resolvedJobName = trim(
            $jobName
        );

        try {
            $job = $this->resolveJob(
                $resolvedJobName
            );

            $deletedCount =
                $this->deleteInBatches(
                    table: $job['table'],

                    timestampColumn: $job['timestampColumn'],

                    retentionDays: $job['retentionDays'],

                    conditions: $job['conditions'],

                    batchSize: $job['batchSize']
                );

            /*
         * Informational logs remain in the normal file log. The database
         * handler is configured for warning and above only.
         */
            log_message(
                'info',
                'Table cleanup completed. '
                    . 'Job: {job}; deleted: {count}.',
                [
                    'job' =>
                    $resolvedJobName,

                    'count' =>
                    $deletedCount,
                ]
            );

            return TableCleanupResult::success(
                $resolvedJobName,
                $deletedCount
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception,
                'error',
                InfrastructureErrorContext::forOperation(
                    operation: 'table_cleanup_job',

                    component: self::class,

                    method: __FUNCTION__,

                    additionalContext: [
                        'cleanup_job' =>
                        $resolvedJobName,
                    ]
                )
            );

            return TableCleanupResult::failure(
                $resolvedJobName,
                $exception->getMessage()
            );
        }
    }

    /**
     * Resolve and validate one configured cleanup job.
     *
     * @return array{
     *     table: string,
     *     timestampColumn: string,
     *     retentionDays: int,
     *     conditions: list<array{
     *         column: string,
     *         operator: string,
     *         value?: scalar|null
     *     }>,
     *     batchSize: int
     * }
     */
    private function resolveJob(
        string $jobName
    ): array {
        $resolvedJobName = trim($jobName);

        if (
            $resolvedJobName === ''
            || ! isset(
                $this->configuration
                    ->jobs[$resolvedJobName]
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown cleanup job "%s".',
                    $resolvedJobName
                )
            );
        }

        $job = $this->configuration
            ->jobs[$resolvedJobName];

        $this->assertIdentifier(
            $job['table'],
            'table'
        );

        $this->assertIdentifier(
            $job['timestampColumn'],
            'timestamp column'
        );

        if ($job['retentionDays'] < 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cleanup job "%s" must retain records for at least one day.',
                    $resolvedJobName
                )
            );
        }

        if ($job['batchSize'] < 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cleanup job "%s" must have a positive batch size.',
                    $resolvedJobName
                )
            );
        }

        foreach ($job['conditions'] as $condition) {
            $this->assertIdentifier(
                $condition['column'],
                'condition column'
            );

            $operator = strtoupper(
                trim($condition['operator'])
            );

            if (
                ! in_array(
                    $operator,
                    self::ALLOWED_OPERATORS,
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported cleanup operator "%s".',
                        $operator
                    )
                );
            }
        }

        return $job;
    }

    /**
     * Delete matching records in bounded batches.
     *
     * Batch deletion avoids one very large transaction when a table contains
     * a substantial historical backlog.
     *
     * PostgreSQL does not directly support DELETE ... LIMIT. A subquery first
     * selects a bounded set of physical rows through ctid and then deletes
     * those selected rows.
     *
     * @param list<array{
     *     column: string,
     *     operator: string,
     *     value?: scalar|null
     * }> $conditions
     */
    private function deleteInBatches(
        string $table,
        string $timestampColumn,
        int $retentionDays,
        array $conditions,
        int $batchSize
    ): int {
        $cutoff = new DateTimeImmutable(
            sprintf(
                '-%d days',
                $retentionDays
            ),
            new DateTimeZone('UTC')
        );

        $cutoffValue = $cutoff->format(
            'Y-m-d H:i:s'
        );

        $totalDeleted = 0;

        do {
            $queryParts = [
                sprintf(
                    '%s < ?',
                    $this->database
                        ->protectIdentifiers(
                            $timestampColumn
                        )
                ),
            ];

            $bindings = [
                $cutoffValue,
            ];

            foreach ($conditions as $condition) {
                $column = $this->database
                    ->protectIdentifiers(
                        $condition['column']
                    );

                $operator = strtoupper(
                    trim($condition['operator'])
                );

                if (
                    $operator === 'IS NULL'
                    || $operator === 'IS NOT NULL'
                ) {
                    $queryParts[] = sprintf(
                        '%s %s',
                        $column,
                        $operator
                    );

                    continue;
                }

                $queryParts[] = sprintf(
                    '%s %s ?',
                    $column,
                    $operator
                );

                $bindings[] =
                    $condition['value'] ?? null;
            }

            /*
             * LIMIT cannot be bound as a normal prepared-statement value on
             * every PostgreSQL driver configuration. It is safe here because
             * batchSize is validated as an integer from trusted config.
             */
            $sql = sprintf(
                <<<'SQL'
DELETE FROM %s
WHERE ctid IN
(
    SELECT ctid
    FROM %s
    WHERE %s
    ORDER BY %s ASC
    LIMIT %d
)
SQL,
                $this->database
                    ->protectIdentifiers($table),
                $this->database
                    ->protectIdentifiers($table),
                implode(
                    ' AND ',
                    $queryParts
                ),
                $this->database
                    ->protectIdentifiers(
                        $timestampColumn
                    ),
                $batchSize
            );

            $this->database->query(
                $sql,
                $bindings
            );

            $batchDeleted = max(
                0,
                $this->database->affectedRows()
            );

            $totalDeleted += $batchDeleted;
        } while ($batchDeleted === $batchSize);

        return $totalDeleted;
    }

    /**
     * Ensure table and column names use safe SQL identifier characters.
     */
    private function assertIdentifier(
        string $identifier,
        string $label
    ): void {
        if (
            preg_match(
                '/^[A-Za-z_][A-Za-z0-9_]*$/',
                $identifier
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid cleanup %s "%s".',
                    $label,
                    $identifier
                )
            );
        }
    }
}
