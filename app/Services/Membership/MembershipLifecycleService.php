<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipModel;
use App\Services\Email\MemberEmailService;
use RuntimeException;
use Throwable;

/**
 * Membership lifecycle housekeeping.
 *
 * IMPORTANT:
 *
 * This service is NOT the authorization boundary.
 *
 * MembershipService continues checking starts_at/expires_at at request time.
 * This service only keeps persisted lifecycle status aligned with elapsed
 * commercial periods for:
 *
 * - history;
 * - administration;
 * - reporting;
 * - renewal/payment workflows;
 * - lifecycle communication.
 */
final class MembershipLifecycleService
{
    public function __construct(
        private readonly MemberMembershipModel
        $membershipModel,

        /*
         * External communication remains downstream from lifecycle state.
         *
         * Membership expiry itself must succeed even when the member has no
         * verified email or the email queue/provider is temporarily unavailable.
         */
        private readonly MemberEmailService
        $memberEmailService
    ) {}

    /**
     * Expire ACTIVE memberships whose expires_at has passed.
     *
     * Processing is batched so one invocation cannot accidentally hold a
     * very large set in memory.
     *
     * Authorization does NOT depend on this method having executed because
     * MembershipService independently checks expires_at at request time.
     *
     * @return array{
     *     scanned:int,
     *     expired:int,
     *     failed:int,
     *     remaining:int
     * }
     */
    public function expireDueMemberships(
        int $batchSize = 500
    ): array {
        $batchSize =
            max(
                1,
                min(
                    1000,
                    $batchSize
                )
            );

        $nowUtc =
            $this->nowUtc();

        $memberships =
            $this
            ->membershipModel
            ->expiredActiveMemberships(
                $nowUtc,
                $batchSize
            );

        $expired =
            0;

        $failed =
            0;

        foreach (
            $memberships
            as $membership
        ) {
            $membershipId =
                max(
                    0,
                    (int) (
                        $membership['id']
                        ?? 0
                    )
                );

            $memberUserId =
                max(
                    0,
                    (int) (
                        $membership['user_id']
                        ?? 0
                    )
                );

            if (
                $membershipId <= 0
                || $memberUserId <= 0
            ) {
                $failed++;

                continue;
            }

            try {
                if (
                    !$this
                        ->membershipModel
                        ->markExpired(
                            $membershipId
                        )
                ) {
                    /*
                     * A concurrent process may already have expired,
                     * replaced or cancelled the row.
                     *
                     * Re-read only when necessary.
                     */
                    $current =
                        $this
                        ->membershipModel
                        ->find(
                            $membershipId
                        );

                    if (
                        is_array($current)
                        && mb_strtoupper(
                            trim(
                                (string) (
                                    $current['status']
                                    ?? ''
                                )
                            )
                        )
                        !== MemberMembershipModel
                        ::STATUS_ACTIVE
                    ) {
                        /*
                         * Another process already transitioned the row.
                         *
                         * Do not send an expiry email from this invocation
                         * because this process did not own the transition.
                         */
                        continue;
                    }

                    throw new RuntimeException(
                        'The membership status could not be updated.'
                    );
                }

                $expired++;

                /*
                 * ------------------------------------------------------
                 * Downstream lifecycle communication
                 * ------------------------------------------------------
                 *
                 * The membership is already EXPIRED.
                 *
                 * Email failure is isolated inside MemberEmailService and
                 * therefore does not change the lifecycle result.
                 */
                $this
                    ->memberEmailService
                    ->queueMembershipExpired(
                        recipientUserId: $memberUserId,

                        membershipId: $membershipId,

                        planName: trim(
                            (string) (
                                $membership['plan_name_snapshot']
                                ?? ''
                            )
                        ),

                        expiresAt: trim(
                            (string) (
                                $membership['expires_at']
                                ?? ''
                            )
                        )
                    );
            } catch (Throwable) {
                /*
                 * Do not stop the entire batch because one historical row
                 * is malformed or cannot be transitioned.
                 *
                 * The CLI wrapper remains responsible for reporting the
                 * aggregate lifecycle failure.
                 */
                $failed++;
            }
        }

        return [
            'scanned' =>
            count(
                $memberships
            ),

            'expired' =>
            $expired,

            'failed' =>
            $failed,

            'remaining' =>
            $this
                ->membershipModel
                ->expiredActiveCount(
                    $nowUtc
                ),
        ];
    }

    /**
     * Application/database timestamps are UTC.
     */
    private function nowUtc(): string
    {
        return (
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone(
                    'UTC'
                )
            )
        )->format(
            'Y-m-d H:i:s'
        );
    }
}
