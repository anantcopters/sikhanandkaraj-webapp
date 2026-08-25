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
 * - validate Superadmin changes;
 * - atomically replace the active configuration;
 * - retain historical configurations.
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

    public function __construct(
        private readonly MatchScoreConfigurationModel
        $configurationModel,

        private readonly BaseConnection
        $db
    ) {}

    /**
     * Return the effective scoring weights.
     *
     * Safe defaults are returned when:
     *
     * - the configuration table has not yet been populated;
     * - a corrupt/legacy row does not total 100;
     * - any persisted value is outside its allowed range.
     *
     * Search therefore remains operational even when administrative
     * configuration is temporarily unavailable.
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
        $row = $this
            ->configurationModel
            ->activeConfiguration();

        if (!is_array($row)) {
            return $this->defaults();
        }

        $weights = [
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

        if (!$this->isValid($weights)) {
            return $this->defaults();
        }

        return $weights;
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
            (int) (
                $input['preference']
                ?? -1
            ),

            'profileCompletion' =>
            (int) (
                $input['profileCompletion']
                ?? -1
            ),

            'approvedPhotos' =>
            (int) (
                $input['approvedPhotos']
                ?? -1
            ),

            'trust' =>
            (int) (
                $input['trust']
                ?? -1
            ),

            'commercial' =>
            (int) (
                $input['commercial']
                ?? -1
            ),
        ];

        if (!$this->isValid($weights)) {
            throw new RuntimeException(
                'Match Score weights are invalid. '
                    . 'All values must be non-negative, '
                    . 'Commercial cannot exceed '
                    . self::MAX_COMMERCIAL_WEIGHT
                    . '%, and the total must equal 100%.'
            );
        }

        $this->db->transBegin();

        try {
            /*
             * Lock the configuration table before replacing the active row.
             *
             * This prevents two simultaneous Superadmin requests from both
             * attempting to create the active configuration.
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

            if ($this->db->transStatus() === false) {
                throw new RuntimeException(
                    'Unable to commit Match Score configuration.'
                );
            }

            $this->db->transCommit();

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
     * Return immutable documented fallback weights.
     *
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
     * Validate one complete weight set.
     *
     * @param array{
     *     preference:int,
     *     profileCompletion:int,
     *     approvedPhotos:int,
     *     trust:int,
     *     commercial:int
     * } $weights
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
}
