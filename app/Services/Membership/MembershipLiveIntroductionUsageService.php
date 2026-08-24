<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Exceptions\MembershipLiveIntroductionQuotaExceededException;
use App\Models\MemberMembershipLiveIntroductionViewModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Owns commercial Live Introduction allowance consumption.
 *
 * Authorization has already succeeded before this service is called.
 *
 * This service therefore does not know about:
 *
 * - gender;
 * - Interest relationships;
 * - blocked members;
 * - video visibility;
 * - profile verification.
 *
 * Those belong to the access-policy layer.
 */
final class MembershipLiveIntroductionUsageService
{
    public function __construct(
        private readonly MemberMembershipLiveIntroductionViewModel
        $usageModel,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Consume one approved Live Introduction or record a replay.
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
             * Serialize all new Live Introduction consumption for this
             * membership.
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
             * Never trust membership ownership merely because an upstream
             * service supplied the membership ID.
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
             * Re-check commercial state while holding the lock.
             *
             * MembershipService already resolves only currently active
             * memberships, but this transaction must remain safe even if the
             * membership changes between authorization and consumption.
             */
            if (
                mb_strtoupper(
                    trim(
                        (string) (
                            $lockedMembership['status']
                            ?? ''
                        )
                    )
                ) !== 'ACTIVE'
            ) {
                throw new RuntimeException(
                    'The membership is no longer active.'
                );
            }

            /*
             * A replay of the same approved video version never consumes
             * another allowance.
             */
            if (
                $this->usageModel
                ->hasConsumedVideo(
                    $membershipId,
                    $videoIntroductionId
                )
            ) {
                $this->usageModel
                    ->recordRepeatView(
                        $membershipId,
                        $videoIntroductionId,
                        $nowUtc
                    );

                $used = $this
                    ->usageModel
                    ->consumedCount(
                        $membershipId
                    );

                $this->commitOrFail();

                return [
                    'consumedNewAllowance' =>
                    false,

                    'membershipUsed' =>
                    $used,

                    'membershipLimit' =>
                    $membershipLimit,
                ];
            }

            $used = $this
                ->usageModel
                ->consumedCount(
                    $membershipId
                );

            if ($used >= $membershipLimit) {
                throw new MembershipLiveIntroductionQuotaExceededException(
                    'Your membership Live Introduction viewing limit '
                        . 'has been reached.'
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
                    'The Live Introduction allowance could not be recorded.'
                );
            }

            $used++;

            $this->commitOrFail();

            return [
                'consumedNewAllowance' =>
                true,

                'membershipUsed' =>
                $used,

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
                'The Live Introduction usage transaction failed.'
            );
        }

        $this->database
            ->transCommit();
    }
}
