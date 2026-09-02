<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipModel;
use App\Services\Email\MemberEmailService;
use Config\DateDisplay as DateDisplayConfig;
use DateTimeImmutable;
use DateTimeZone;
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
 * commercial periods and emits lifecycle communication.
 */
final class MembershipLifecycleService
{
    /**
     * Send one renewal reminder exactly three member-facing
     * calendar days before expiry.
     */
    private const EXPIRY_REMINDER_DAYS =
    3;

    /**
     * Bound one reminder execution even if an unexpectedly large
     * number of memberships share the same expiry date.
     *
     * 20 batches × maximum 1000 rows = maximum 20,000 rows/run.
     */
    private const MAXIMUM_REMINDER_BATCHES =
    20;

    public function __construct(
        private readonly MemberMembershipModel
        $membershipModel,

        /*
         * External communication remains downstream from lifecycle state.
         *
         * Membership expiry and authorization must succeed even when the
         * member has no verified email or email infrastructure is unavailable.
         */
        private readonly MemberEmailService
        $memberEmailService
    ) {}

    /**
     * Queue expiry reminders for ACTIVE memberships expiring exactly
     * three member-facing calendar days from today.
     *
     * Calendar-day semantics are intentional.
     *
     * Example:
     *
     * Morning job date: 31-Aug-2026
     * Reminder target:  03-Sep-2026
     *
     * The configured display timezone is converted to UTC boundaries before
     * querying because membership timestamps remain stored in UTC.
     *
     * @return array{
     *     scanned:int,
     *     queued:int,
     *     skipped:int,
     *     failed:int,
     *     target_date:string
     * }
     */
    public function queueExpiryReminders(
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

        [
            $startsAtUtc,
            $endsAtUtc,
            $targetDate,
        ] =
            $this
            ->expiryReminderWindow();

        $scanned =
            0;

        $queued =
            0;

        $skipped =
            0;

        $failed =
            0;

        $afterMembershipId =
            0;

        for (
            $batch = 1;
            $batch
                <= self::MAXIMUM_REMINDER_BATCHES;
            $batch++
        ) {
            $memberships =
                $this
                ->membershipModel
                ->expiringActiveMemberships(
                    startsAtUtc: $startsAtUtc,

                    endsAtUtc: $endsAtUtc,

                    limit: $batchSize,

                    afterMembershipId: $afterMembershipId
                );

            if ($memberships === []) {
                break;
            }

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

                /*
                 * Always advance the cursor using the row returned
                 * by the ordered model query.
                 */
                if (
                    $membershipId
                    > $afterMembershipId
                ) {
                    $afterMembershipId =
                        $membershipId;
                }

                $memberUserId =
                    max(
                        0,
                        (int) (
                            $membership['user_id']
                            ?? 0
                        )
                    );

                $scanned++;

                if (
                    $membershipId <= 0
                    || $memberUserId <= 0
                ) {
                    $failed++;

                    continue;
                }

                try {
                    /*
                     * MemberEmailService owns:
                     *
                     * - verified-primary-email eligibility;
                     * - email definition;
                     * - reminder idempotency;
                     * - queue insertion;
                     * - optional-email failure isolation.
                     */
                    $queueId =
                        $this
                        ->memberEmailService
                        ->queueMembershipExpiringSoon(
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

                    if ($queueId !== null) {
                        $queued++;

                        continue;
                    }

                    /*
                     * null is expected when:
                     *
                     * - the reminder was already queued; or
                     * - the member has no verified primary email.
                     *
                     * Neither condition is a lifecycle failure.
                     */
                    $skipped++;
                } catch (Throwable) {
                    /*
                     * One malformed/historical membership must not stop
                     * reminders for every other member in the batch.
                     */
                    $failed++;
                }
            }

            /*
             * A short page means there cannot be another page.
             */
            if (
                count(
                    $memberships
                ) < $batchSize
            ) {
                break;
            }
        }

        return [
            'scanned' =>
            $scanned,

            'queued' =>
            $queued,

            'skipped' =>
            $skipped,

            'failed' =>
            $failed,

            'target_date' =>
            $targetDate,
        ];
    }

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
                         * Another process owns the lifecycle transition.
                         * Do not send another expiry email here.
                         */
                        continue;
                    }

                    throw new RuntimeException(
                        'The membership status could not be updated.'
                    );
                }

                $expired++;

                /*
                 * Membership is already EXPIRED at this point.
                 *
                 * Email remains a downstream optional communication.
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
                 * Do not stop the complete lifecycle batch because one
                 * historical membership cannot be processed.
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
     * Build the UTC database window corresponding to the configured
     * member-facing calendar day exactly EXPIRY_REMINDER_DAYS ahead.
     *
     * @return array{0:string,1:string,2:string}
     */
    private function expiryReminderWindow(): array
    {
        /** @var DateDisplayConfig $configuration */
        $configuration =
            config(
                DateDisplayConfig::class
            );

        $timezoneName =
            trim(
                $configuration
                    ->timezone
            );

        if ($timezoneName === '') {
            $timezoneName =
                'Asia/Kolkata';
        }

        $displayTimezone =
            new DateTimeZone(
                $timezoneName
            );

        $utcTimezone =
            new DateTimeZone(
                'UTC'
            );

        /*
         * Start with "today" in the member-facing timezone,
         * not the server timezone.
         */
        $targetStart =
            (
                new DateTimeImmutable(
                    'today',
                    $displayTimezone
                )
            )
            ->modify(
                '+'
                    . self::EXPIRY_REMINDER_DAYS
                    . ' days'
            );

        $targetEnd =
            $targetStart
            ->modify(
                '+1 day'
            );

        /*
         * Convert the local calendar-day boundaries back to UTC because
         * member_memberships.expires_at remains an authoritative UTC value.
         */
        $startsAtUtc =
            $targetStart
            ->setTimezone(
                $utcTimezone
            )
            ->format(
                'Y-m-d H:i:s'
            );

        $endsAtUtc =
            $targetEnd
            ->setTimezone(
                $utcTimezone
            )
            ->format(
                'Y-m-d H:i:s'
            );

        return [
            $startsAtUtc,
            $endsAtUtc,

            /*
             * CLI/reporting only.
             */
            $targetStart
                ->format(
                    'Y-m-d'
                ),
        ];
    }

    /**
     * Application/database timestamps are UTC.
     */
    private function nowUtc(): string
    {
        return (
            new DateTimeImmutable(
                'now',
                new DateTimeZone(
                    'UTC'
                )
            )
        )->format(
            'Y-m-d H:i:s'
        );
    }
}
