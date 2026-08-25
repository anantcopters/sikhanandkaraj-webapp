<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Exceptions\MembershipLiveIntroductionQuotaExceededException;
use App\Models\MemberMembershipLiveIntroductionViewModel;
use App\Models\MemberMembershipModel;
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
 *
 * CONCURRENCY AUTHORITY
 * =====================
 *
 * Live Introduction authorization may resolve a membership before another
 * request replaces or expires it.
 *
 * Therefore the membership row locked during commercial consumption is the
 * final authority for:
 *
 * - ownership;
 * - ACTIVE status;
 * - starts_at / expires_at;
 * - immutable purchased quota.
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
     * Consumption is scoped to membership + video owner, not to a specific video
     * version. Replaying the same video or viewing a replacement approved video
     * from the same member therefore does not consume another allowance.
     *
     * The membership is locked and commercially revalidated inside the
     * transaction before usage is recorded.
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

        if (
            $viewerUserId <= 0
            || $ownerUserId <= 0
            || $videoIntroductionId <= 0
            || $membershipId <= 0
        ) {
            throw new RuntimeException(
                'The Live Introduction allowance is unavailable.'
            );
        }

        $nowUtc = (
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone(
                    'UTC'
                )
            )
        )->format(
            'Y-m-d H:i:s'
        );

        $this->database
            ->transBegin();

        try {
            /*
             * Serialize every commercial Live Introduction usage operation
             * against the purchased membership row.
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
             * Never trust a membership identifier supplied indirectly by
             * another application layer.
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
             * Revalidate commercial usability AFTER acquiring the lock.
             *
             * This closes the race where an access policy authorizes playback
             * using membership A while another request replaces membership A
             * with membership B before commercial usage is recorded.
             */
            $this->assertMembershipIsUsable(
                $lockedMembership,
                $nowUtc
            );

            /*
             * Quota comes from the immutable purchased membership snapshot.
             *
             * Do not trust the earlier application-layer projection because
             * the locked database row is the commercial authority at the
             * consumption boundary.
             */
            $membershipLimit = max(
                0,
                (int) (
                    $lockedMembership['live_introduction_view_limit_snapshot']
                    ?? 0
                )
            );

            if ($membershipLimit <= 0) {
                throw new RuntimeException(
                    'The Live Introduction allowance is unavailable.'
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
     * Verify that the locked membership is still commercially usable.
     *
     * The expiry timestamp is checked directly so authorization does not depend
     * on lifecycle housekeeping having already changed ACTIVE -> EXPIRED.
     *
     * @param array<string, mixed> $membership
     */
    private function assertMembershipIsUsable(
        array $membership,
        string $nowUtc
    ): void {
        $status = mb_strtoupper(
            trim(
                (string) (
                    $membership['status']
                    ?? ''
                )
            )
        );

        $startsAt = trim(
            (string) (
                $membership['starts_at']
                ?? ''
            )
        );

        $expiresAt = trim(
            (string) (
                $membership['expires_at']
                ?? ''
            )
        );

        if (
            $status
            !== MemberMembershipModel::STATUS_ACTIVE
            || $startsAt === ''
            || $expiresAt === ''
            || $startsAt > $nowUtc
            || $expiresAt <= $nowUtc
        ) {
            throw new RuntimeException(
                'The membership is no longer active. '
                    . 'Please refresh and try again.'
            );
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
