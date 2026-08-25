<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Models\MemberMatchCandidateModel;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Development/QA-only PostgreSQL candidate-query profiler.
 *
 * Membership-26.
 *
 * This service:
 *
 * - compiles the real MemberMatchCandidateModel candidate query;
 * - asks PostgreSQL for EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON);
 * - does not maintain another candidate-selection implementation;
 * - is unavailable in production;
 * - is intended only for CLI diagnostics.
 */
final class DevelopmentCandidateQueryProfilerService
{
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Profile the authoritative eligible-candidate query.
     *
     * @return array{
     *     memberId:int,
     *     profileReference:string,
     *     gender:string,
     *     sql:string,
     *     planningTimeMs:float,
     *     executionTimeMs:float,
     *     plan:array<string, mixed>
     * }
     */
    public function profileEligibleCandidates(
        int $memberId
    ): array {
        $this->assertAllowedEnvironment();

        if ($memberId <= 0) {
            throw new DomainException(
                'A valid member ID is required.'
            );
        }

        $member =
            $this->userModel
            ->find(
                $memberId
            );

        if (!is_array($member)) {
            throw new DomainException(
                'The requested member account could not be found.'
            );
        }

        $gender =
            trim(
                (string) (
                    $member['gender']
                    ?? ''
                )
            );

        if ($gender === '') {
            throw new DomainException(
                'The requested member does not have a valid gender.'
            );
        }

        /*
         * Compile the real Dashboard/Matchmaking candidate query.
         *
         * No candidate eligibility SQL is duplicated in this development
         * service.
         */
        $sql =
            $this->candidateModel
            ->compiledEligibleCandidatesSql(
                $memberId,
                $gender
            );

        if ($sql === '') {
            throw new RuntimeException(
                'The candidate query could not be compiled.'
            );
        }

        /*
         * ANALYZE executes the SELECT so PostgreSQL can report real timings.
         *
         * The candidate query itself is read-only.
         *
         * BUFFERS tells us whether PostgreSQL is reading from shared cache or
         * performing significant physical/database-page work.
         *
         * JSON avoids brittle parsing of PostgreSQL's human-readable EXPLAIN
         * output.
         */
        $explainSql =
            'EXPLAIN (
                ANALYZE TRUE,
                BUFFERS TRUE,
                FORMAT JSON
            ) '
            . $sql;

        try {
            $query =
                $this->database
                ->query(
                    $explainSql
                );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'PostgreSQL EXPLAIN failed: '
                    . $exception->getMessage(),
                0,
                $exception
            );
        }

        $row =
            $query->getRowArray();

        if (!is_array($row)) {
            throw new RuntimeException(
                'PostgreSQL did not return an execution plan.'
            );
        }

        /*
         * PostgreSQL/CI driver may expose the EXPLAIN JSON column under
         * "QUERY PLAN" or a normalized equivalent.
         *
         * Use the first returned value instead of depending on driver-specific
         * column-name casing.
         */
        $rawPlan =
            reset(
                $row
            );

        if (is_array($rawPlan)) {
            $decoded =
                $rawPlan;
        } else {
            $decoded =
                json_decode(
                    (string) $rawPlan,
                    true
                );
        }

        if (
            !is_array($decoded)
            || !isset($decoded[0])
            || !is_array($decoded[0])
        ) {
            throw new RuntimeException(
                'PostgreSQL returned an invalid JSON execution plan.'
            );
        }

        $root =
            $decoded[0];

        $plan =
            is_array(
                $root['Plan']
                    ?? null
            )
            ? $root['Plan']
            : [];

        return [
            'memberId' =>
            $memberId,

            'profileReference' =>
            trim(
                (string) (
                    $member['profile_ref_number']
                    ?? ''
                )
            ),

            'gender' =>
            $gender,

            'sql' =>
            $sql,

            'planningTimeMs' =>
            round(
                (float) (
                    $root['Planning Time']
                    ?? 0.0
                ),
                3
            ),

            'executionTimeMs' =>
            round(
                (float) (
                    $root['Execution Time']
                    ?? 0.0
                ),
                3
            ),

            'plan' =>
            $plan,
        ];
    }

    /**
     * Search an execution-plan tree for potentially important operations.
     *
     * This does not decide whether PostgreSQL made a bad choice. Sequential
     * scans can be correct for small tables.
     *
     * It merely exposes the operations we should inspect when evaluating the
     * plan.
     *
     * @param array<string, mixed> $plan
     *
     * @return list<array<string, mixed>>
     */
    public function importantOperations(
        array $plan
    ): array {
        $operations = [];

        $this->collectImportantOperations(
            $plan,
            $operations,
            0
        );

        return $operations;
    }

    /**
     * Recursively walk PostgreSQL's plan tree.
     *
     * @param array<string, mixed>       $node
     * @param list<array<string, mixed>> $operations
     */
    private function collectImportantOperations(
        array $node,
        array &$operations,
        int $depth
    ): void {
        $nodeType =
            trim(
                (string) (
                    $node['Node Type']
                    ?? ''
                )
            );

        $actualRows =
            max(
                0,
                (int) (
                    $node['Actual Rows']
                    ?? 0
                )
            );

        $loops =
            max(
                0,
                (int) (
                    $node['Actual Loops']
                    ?? 0
                )
            );

        $totalTime =
            max(
                0.0,
                (float) (
                    $node['Actual Total Time']
                    ?? 0.0
                )
            );

        /*
         * Surface operations useful for Phase-6 diagnosis.
         *
         * Do not automatically label a Seq Scan as defective. PostgreSQL often
         * correctly chooses one for small tables.
         */
        if (
            in_array(
                $nodeType,
                [
                    'Seq Scan',
                    'Index Scan',
                    'Index Only Scan',
                    'Bitmap Heap Scan',
                    'Bitmap Index Scan',
                    'Nested Loop',
                    'Hash Join',
                    'Merge Join',
                    'Sort',
                    'Aggregate',
                    'HashAggregate',
                ],
                true
            )
        ) {
            $operations[] = [
                'depth' =>
                $depth,

                'nodeType' =>
                $nodeType,

                'relation' =>
                trim(
                    (string) (
                        $node['Relation Name']
                        ?? ''
                    )
                ),

                'index' =>
                trim(
                    (string) (
                        $node['Index Name']
                        ?? ''
                    )
                ),

                'actualRows' =>
                $actualRows,

                'loops' =>
                $loops,

                'totalTimeMs' =>
                round(
                    $totalTime,
                    3
                ),

                'sharedHitBlocks' =>
                max(
                    0,
                    (int) (
                        $node['Shared Hit Blocks']
                        ?? 0
                    )
                ),

                'sharedReadBlocks' =>
                max(
                    0,
                    (int) (
                        $node['Shared Read Blocks']
                        ?? 0
                    )
                ),
            ];
        }

        $children =
            $node['Plans']
            ?? [];

        if (!is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }

            $this->collectImportantOperations(
                $child,
                $operations,
                $depth + 1
            );
        }
    }

    /**
     * Keep EXPLAIN ANALYZE completely outside Production.
     */
    private function assertAllowedEnvironment(): void
    {
        $deployment =
            strtolower(
                trim(
                    (string) env(
                        'APP_DEPLOYMENT',
                        ''
                    )
                )
            );

        if (
            !in_array(
                $deployment,
                [
                    'development',
                    'qa',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Candidate query profiling is available only in development or QA.'
            );
        }

        if (
            filter_var(
                env(
                    'DEVELOPMENT_SEARCH_PROFILER_ENABLED',
                    false
                ),
                FILTER_VALIDATE_BOOLEAN
            ) !== true
        ) {
            throw new RuntimeException(
                'Development Search profiling is disabled.'
            );
        }
    }
}
