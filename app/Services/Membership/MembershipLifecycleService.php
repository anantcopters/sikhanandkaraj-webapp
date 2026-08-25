<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipModel;
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
 * - future renewal/payment workflows.
 */
final class MembershipLifecycleService
{
    public function __construct(
        private readonly MemberMembershipModel
        $membershipModel
    ) {}

    /**
     * Expire ACTIVE memberships whose expires_at has passed.
     *
     * Processing is batched so one invocation cannot accidentally hold a very
     * large set in memory.
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
            $this->membershipModel
            ->expiredActiveMemberships(
                $nowUtc,
                $batchSize
            );

        $expired = 0;

        $failed = 0;

        foreach ($memberships as $membership) {
            $membershipId =
                max(
                    0,
                    (int) (
                        $membership['id']
                        ?? 0
                    )
                );

            if ($membershipId <= 0) {
                $failed++;

                continue;
            }

            try {
                if (
                    !$this->membershipModel
                        ->markExpired(
                            $membershipId
                        )
                ) {
                    /*
                     * A concurrent process may already have expired/replaced/
                     * cancelled the row.
                     *
                     * Re-read only when necessary.
                     */
                    $current =
                        $this->membershipModel
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
                        ) !== MemberMembershipModel::STATUS_ACTIVE
                    ) {
                        continue;
                    }

                    throw new RuntimeException(
                        'The membership status could not be updated.'
                    );
                }

                $expired++;
            } catch (Throwable) {
                /*
                 * Do not stop the entire batch because one historical row is
                 * malformed.
                 *
                 * The CLI wrapper logs/report failures and returns a non-zero
                 * exit status when required.
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
            $this->membershipModel
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
