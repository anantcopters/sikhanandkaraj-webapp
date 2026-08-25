<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Exceptions\MembershipLiveIntroductionQuotaExceededException;
use App\Models\MemberMembershipLiveIntroductionViewModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Owns membership-scoped Live Introduction allowance consumption.
 *
 * Commercial consumption is candidate-scoped:
 *
 *     membership_id + owner_user_id
 *
 * It is deliberately NOT video-version scoped.
 */
final class MembershipLiveIntroductionUsageService
{
    public function __construct(
        private readonly
        MemberMembershipLiveIntroductionViewModel
        $usageModel,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Return whether this candidate has already consumed allowance during the
     * supplied membership.
     */
    public function hasConsumed(
        int $membershipId,
        int $ownerUserId
    ): bool {
        return $this
            ->usageModel
            ->hasConsumedOwner(
                $membershipId,
                $ownerUserId
            );
    }

    /**
     * Consume one Live Introduction allowance or record a repeat playback.
     *
     * @param array<string, mixed> $membership
     *
     * @return array{
     *     consumedNewAllowance:bool,
     *     membershipUsed:int,
     *     membershipLimit:int
     * }
     */
    public function recordAuthorizedView(
        int $viewerUserId,
        int $ownerUserId,
        int $videoIntroductionId,
        array $membership
    ): array {
        $membershipId = max(
            0,
            (int) (
                $membership['id']
                ?? 0
            )
        );

        $membershipLimit = max(
            0,
            (int) (
                $membership['liveIntroductionViewLimit']
                ?? 0
            )
        );

        if (
            $viewerUserId <= 0
            || $ownerUserId <= 0
            || $videoIntroductionId <= 0
            || $membershipId <= 0
            || $membershipLimit <= 0
        ) {
            throw new RuntimeException(
                'The Live Introduction allowance is unavailable.'
            );
        }

        $nowUtc = (
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone('UTC')
            )
        )->format(
            'Y-m-d H:i:s'
        );

        $this->database
            ->transBegin();

        try {
            /*
             * Serialize commercial usage for this membership.
             */
            $lockedMembership = $this
                ->usageModel
                ->lockMembership(
                    $membershipId
                );

            if (!is_array($lockedMembership)) {
                throw new RuntimeException(
                    'The active membership could not be resolved.'
                );
            }

            /*
             * Membership IDs must never be trusted without re-checking
             * ownership while the authoritative membership row is locked.
             */
            if (
                (int) (
                    $lockedMembership['user_id']
                    ?? 0
                ) !== $viewerUserId
            ) {
                throw new RuntimeException(
                    'The membership does not belong to this member.'
                );
            }

            /*
             * Candidate already consumed during this membership.
             *
             * This includes:
             *
             * - replay of the same video;
             * - playback of a newly approved replacement video belonging to
             *   the same member.
             *
             * Neither consumes another commercial allowance.
             */
            if (
                $this->usageModel
                ->hasConsumedOwner(
                    $membershipId,
                    $ownerUserId
                )
            ) {
                $this->usageModel
                    ->recordRepeatView(
                        $membershipId,
                        $ownerUserId,
                        $videoIntroductionId,
                        $nowUtc
                    );

                $membershipUsed = $this
                    ->usageModel
                    ->consumedCount(
                        $membershipId
                    );

                $this->commitOrFail();

                return [
                    'consumedNewAllowance' =>
                    false,

                    'membershipUsed' =>
                    $membershipUsed,

                    'membershipLimit' =>
                    $membershipLimit,
                ];
            }

            $membershipUsed = $this
                ->usageModel
                ->consumedCount(
                    $membershipId
                );

            if (
                $membershipUsed
                >= $membershipLimit
            ) {
                throw new
                    MembershipLiveIntroductionQuotaExceededException(
                        'Your membership Live Introduction '
                            . 'view limit has been reached.'
                    );
            }

            if (
                !$this->usageModel
                    ->consume(
                        $membershipId,
                        $viewerUserId,
                        $ownerUserId,
                        $videoIntroductionId,
                        $nowUtc
                    )
            ) {
                throw new RuntimeException(
                    'The Live Introduction allowance '
                        . 'could not be recorded.'
                );
            }

            $membershipUsed++;

            $this->commitOrFail();

            return [
                'consumedNewAllowance' =>
                true,

                'membershipUsed' =>
                $membershipUsed,

                'membershipLimit' =>
                $membershipLimit,
            ];
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * Commit only when CI4 reports a successful transaction.
     */
    private function commitOrFail(): void
    {
        if (
            $this->database
            ->transStatus()
            === false
        ) {
            throw new RuntimeException(
                'The membership usage transaction failed.'
            );
        }

        $this->database
            ->transCommit();
    }
}
