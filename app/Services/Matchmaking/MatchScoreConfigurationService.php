<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MatchScoreConfigurationModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Single authority for Match Score component weights.
 *
 * Responsibilities:
 *
 * - provide safe defaults;
 * - normalize persisted configuration;
 * - validate Super Admin changes;
 * - atomically replace the active configuration;
 * - retain historical configurations;
 * - provide safe read contracts for the administration UI.
 *
 * Match scoring code must consume this service rather than hard-coding
 * component weights independently.
 */
final class MatchScoreConfigurationService
{
    public const DEFAULT_PREFERENCE_WEIGHT =
    55;

    public const DEFAULT_PROFILE_COMPLETION_WEIGHT =
    10;

    public const DEFAULT_APPROVED_PHOTO_WEIGHT =
    10;

    public const DEFAULT_TRUST_WEIGHT =
    15;

    public const DEFAULT_COMMERCIAL_WEIGHT =
    10;

    /*
     * Commercial influence must remain a minority component.
     *
     * The same limit is protected by the database CHECK constraint.
     */
    public const MAX_COMMERCIAL_WEIGHT =
    20;

    /**
     * Request-local configuration cache.
     *
     * One Search may score hundreds of candidates. Match Score configuration
     * is global for the request and therefore must be read only once.
     *
     * @var array{
     *     preference:int,
     *     profileCompletion:int,
     *     approvedPhotos:int,
     *     trust:int,
     *     commercial:int
     * }|null
     */
    private ?array $resolvedWeights =
    null;

    public function __construct(
        private readonly MatchScoreConfigurationModel
        $configurationModel,

        private readonly BaseConnection
        $db
    ) {}

    /**
     * Return effective Match Score weights.
     *
     * Configuration is cached for the lifetime of this service instance so
     * scoring N candidates does not issue N configuration queries.
     *
     * @return array{
     *     preference:int,
     *     profileCompletion:int,
     *     approvedPhotos:int,
     *     trust:int,
     *     commercial:int
     * }
     */
    public function weights(): array
    {
        if (
            $this->resolvedWeights
            !== null
        ) {
            return $this->resolvedWeights;
        }

        $row =
            $this->configurationModel
            ->activeConfiguration();

        if (!is_array($row)) {
            return $this->resolvedWeights =
                $this->defaults();
        }

        $weights =
            $this->weightsFromRow(
                $row
            );

        if (!$this->isValid($weights)) {
            return $this->resolvedWeights =
                $this->defaults();
        }

        return $this->resolvedWeights =
            $weights;
    }

    /**
     * Return the administration-page contract.
     *
     * The UI does not need to know whether the current values came from a
     * persisted row or the application's safe defaults.
     *
     * @return array{
     *     weights:array<string, int>,
     *     persisted:bool,
     *     configurationId:int|null,
     *     createdAt:string|null,
     *     createdByAdminId:int|null
     * }
     */
    public function activeConfiguration(): array
    {
        $row =
            $this->configurationModel
            ->activeConfiguration();

        if (!is_array($row)) {
            return [
                'weights' =>
                $this->weights(),

                'persisted' =>
                false,

                'configurationId' =>
                null,

                'createdAt' =>
                null,

                'createdByAdminId' =>
                null,
            ];
        }

        return [
            'weights' =>
            $this->weights(),

            'persisted' =>
            true,

            'configurationId' =>
            max(
                0,
                (int) (
                    $row['id']
                    ?? 0
                )
            ),

            'createdAt' =>
            $this->nullableString(
                $row['created_at']
                    ?? null
            ),

            'createdByAdminId' =>
            isset(
                $row['created_by_admin_id']
            )
                ? max(
                    0,
                    (int) $row['created_by_admin_id']
                )
                : null,
        ];
    }

    /**
     * Return immutable configuration history for the Super Admin UI.
     *
     * @return list<array<string, mixed>>
     */
    public function history(
        int $limit = 25
    ): array {
        $rows =
            $this->configurationModel
            ->configurationHistory(
                $limit
            );

        $history = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $weights =
                $this->weightsFromRow(
                    $row
                );

            $history[] = [
                'id' =>
                max(
                    0,
                    (int) (
                        $row['id']
                        ?? 0
                    )
                ),

                'weights' =>
                $weights,

                'isActive' =>
                $this->databaseBoolean(
                    $row['is_active']
                        ?? false
                ),

                'createdByAdminId' =>
                isset(
                    $row['created_by_admin_id']
                )
                    ? max(
                        0,
                        (int) $row['created_by_admin_id']
                    )
                    : null,

                'createdAt' =>
                $this->nullableString(
                    $row['created_at']
                        ?? null
                ),
            ];
        }

        return $history;
    }

    /**
     * Persist a new effective configuration.
     *
     * Existing rows are never overwritten. The previous active configuration
     * is deactivated and a new historical snapshot is inserted.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function replaceActiveConfiguration(
        array $input,
        int $adminId
    ): array {
        if ($adminId <= 0) {
            throw new RuntimeException(
                'A valid administrator is required.'
            );
        }

        $weights = [
            'preference' =>
            $this->integerInput(
                $input['preference']
                    ?? null
            ),

            'profileCompletion' =>
            $this->integerInput(
                $input['profileCompletion']
                    ?? null
            ),

            'approvedPhotos' =>
            $this->integerInput(
                $input['approvedPhotos']
                    ?? null
            ),

            'trust' =>
            $this->integerInput(
                $input['trust']
                    ?? null
            ),

            'commercial' =>
            $this->integerInput(
                $input['commercial']
                    ?? null
            ),
        ];

        if (!$this->isValid($weights)) {
            throw new RuntimeException(
                'Match Score weights are invalid. '
                    . 'All values must be whole numbers from 0 to 100, '
                    . 'Commercial cannot exceed '
                    . self::MAX_COMMERCIAL_WEIGHT
                    . '%, and the total must equal 100%.'
            );
        }

        $this->db->transBegin();

        try {
            /*
             * Prevent simultaneous Super Admin requests from both creating an
             * active configuration.
             */
            $this->db->query(
                'LOCK TABLE match_score_configurations '
                    . 'IN EXCLUSIVE MODE'
            );

            $this->configurationModel
                ->where(
                    'is_active',
                    true
                )
                ->set(
                    'is_active',
                    false
                )
                ->update();

            $configurationId =
                $this->configurationModel
                ->insert(
                    [
                        'preference_weight' =>
                        $weights['preference'],

                        'profile_completion_weight' =>
                        $weights['profileCompletion'],

                        'approved_photo_weight' =>
                        $weights['approvedPhotos'],

                        'trust_weight' =>
                        $weights['trust'],

                        'commercial_weight' =>
                        $weights['commercial'],

                        'is_active' =>
                        true,

                        'created_by_admin_id' =>
                        $adminId,

                        'created_at' =>
                        date(
                            'Y-m-d H:i:s'
                        ),
                    ],
                    true
                );

            if ($configurationId === false) {
                throw new RuntimeException(
                    'Unable to save Match Score configuration.'
                );
            }

            if (
                $this->db->transStatus()
                === false
            ) {
                throw new RuntimeException(
                    'Unable to commit Match Score configuration.'
                );
            }

            $this->db->transCommit();

            /*
             * This service instance may already have resolved the old values.
             */
            $this->resolvedWeights =
                $weights;

            return [
                'id' =>
                (int) $configurationId,

                ...$weights,
            ];
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{
     *     preference:int,
     *     profileCompletion:int,
     *     approvedPhotos:int,
     *     trust:int,
     *     commercial:int
     * }
     */
    private function weightsFromRow(
        array $row
    ): array {
        return [
            'preference' =>
            (int) (
                $row['preference_weight']
                ?? -1
            ),

            'profileCompletion' =>
            (int) (
                $row['profile_completion_weight']
                ?? -1
            ),

            'approvedPhotos' =>
            (int) (
                $row['approved_photo_weight']
                ?? -1
            ),

            'trust' =>
            (int) (
                $row['trust_weight']
                ?? -1
            ),

            'commercial' =>
            (int) (
                $row['commercial_weight']
                ?? -1
            ),
        ];
    }

    /**
     * @return array{
     *     preference:int,
     *     profileCompletion:int,
     *     approvedPhotos:int,
     *     trust:int,
     *     commercial:int
     * }
     */
    private function defaults(): array
    {
        return [
            'preference' =>
            self::DEFAULT_PREFERENCE_WEIGHT,

            'profileCompletion' =>
            self::DEFAULT_PROFILE_COMPLETION_WEIGHT,

            'approvedPhotos' =>
            self::DEFAULT_APPROVED_PHOTO_WEIGHT,

            'trust' =>
            self::DEFAULT_TRUST_WEIGHT,

            'commercial' =>
            self::DEFAULT_COMMERCIAL_WEIGHT,
        ];
    }

    /**
     * @param array<string, int> $weights
     */
    private function isValid(
        array $weights
    ): bool {
        foreach ($weights as $weight) {
            if (
                $weight < 0
                || $weight > 100
            ) {
                return false;
            }
        }

        if (
            $weights['commercial']
            > self::MAX_COMMERCIAL_WEIGHT
        ) {
            return false;
        }

        return array_sum(
            $weights
        ) === 100;
    }

    /**
     * Reject decimal/non-numeric values rather than silently converting them.
     */
    private function integerInput(
        mixed $value
    ): int {
        $value = trim(
            (string) $value
        );

        if (
            $value === ''
            || preg_match(
                '/^\d+$/',
                $value
            ) !== 1
        ) {
            return -1;
        }

        return (int) $value;
    }

    private function databaseBoolean(
        mixed $value
    ): bool {
        return in_array(
            $value,
            [
                true,
                1,
                '1',
                't',
                'true',
                'TRUE',
            ],
            true
        );
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }
}
