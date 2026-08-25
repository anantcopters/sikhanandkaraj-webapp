<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Exceptions\MembershipProfileQuotaExceededException;
use App\Models\MemberMembershipModel;
use App\Models\MemberMembershipProfileViewModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Owns membership-scoped Full Profile quota consumption.
 *
 * This service deliberately knows nothing about:
 *
 * - candidate gender;
 * - interests;
 * - blocking;
 * - verification credentials.
 *
 * Those rules belong to ProfileAccessPolicy.
 *
 * Its responsibility begins only after ProfileAccessPolicy has decided that
 * the target is commercially consumable.
 *
 * CONCURRENCY AUTHORITY
 * =====================
 *
 * Authorization and commercial consumption are separate operations.
 *
 * A membership that was ACTIVE when ProfileAccessPolicy resolved it may be
 * replaced, cancelled or expire before quota is actually recorded.
 *
 * Therefore this service MUST revalidate the locked membership immediately
 * before consuming quota. The previously-resolved membership snapshot is
 * advisory input only.
 */
final class MembershipProfileUsageService
{
    private const IST_TIMEZONE =
    'Asia/Kolkata';

    public function __construct(
        private readonly MemberMembershipProfileViewModel
        $usageModel,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Return whether the target has already been consumed in this membership.
     */
    public function hasConsumed(
        int $membershipId,
        int $viewedUserId
    ): bool {
        return $this
            ->usageModel
            ->hasConsumedTarget(
                $membershipId,
                $viewedUserId
            );
    }

    /**
     * Consume one Full Profile allowance or record a repeat opening.
     *
     * IMPORTANT:
     *
     * The supplied membership was resolved before this method was called.
     * Never assume it is still commercially active.
     *
     * The authoritative membership row is locked and revalidated inside this
     * transaction before either first-time or repeat usage is recorded.
     *
     * @param array<string, mixed> $membership
     *
     * @return array{
     *     consumedNewAllowance:bool,
     *     membershipUsed:int,
     *     membershipLimit:int,
     *     dailyUsed:int,
     *     dailyLimit:int
     * }
     */
    public function recordAuthorizedView(
        int $viewerUserId,
        int $viewedUserId,
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
            || $viewedUserId <= 0
            || $membershipId <= 0
        ) {
            throw new RuntimeException(
                'The membership profile allowance is unavailable.'
            );
        }

        $now = new \DateTimeImmutable(
            'now',
            new \DateTimeZone(
                'UTC'
            )
        );

        $nowUtc = $now->format(
            'Y-m-d H:i:s'
        );

        $usageDateIst = $now
            ->setTimezone(
                new \DateTimeZone(
                    self::IST_TIMEZONE
                )
            )
            ->format(
                'Y-m-d'
            );

        $this->database
            ->transBegin();

        try {
            /*
             * Serialize every quota operation for this membership.
             *
             * This protects both:
             *
             * - first-time consumption;
             * - repeat-view accounting.
             *
             * It also synchronizes this operation with membership replacement,
             * because replacement locks the same member/membership records.
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
             * Re-check ownership while holding the authoritative row lock.
             *
             * Never trust a membership ID supplied indirectly by another
             * application layer.
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
             * TIME-OF-CHECK / TIME-OF-USE PROTECTION
             * ======================================
             *
             * ProfileAccessPolicy may have resolved this membership before a
             * simultaneous purchase/renewal/upgrade replaced it.
             *
             * The locked persistence row is therefore the final authority.
             *
             * A REPLACED, CANCELLED or EXPIRED membership must never consume
             * new usage, even when an older application-layer snapshot still
             * refers to it.
             */
            $this->assertMembershipIsUsable(
                $lockedMembership,
                $nowUtc
            );

            /*
             * Commercial limits are taken from the LOCKED immutable purchase
             * snapshot rather than the earlier application-layer membership
             * projection.
             *
             * This prevents stale or accidentally modified caller data from
             * influencing quota enforcement.
             */
            $membershipLimit = max(
                0,
                (int) (
                    $lockedMembership['profile_view_limit_snapshot']
                    ?? 0
                )
            );

            $dailyLimit = max(
                0,
                (int) (
                    $lockedMembership['daily_profile_view_limit_snapshot']
                    ?? 0
                )
            );

            if (
                $membershipLimit <= 0
                || $dailyLimit <= 0
            ) {
                throw new RuntimeException(
                    'The membership profile allowance is unavailable.'
                );
            }

            /*
             * A repeated target never consumes another allowance.
             *
             * This check occurs AFTER the membership lock so simultaneous
             * first-time requests for the same target cannot both consume.
             */
            if (
                $this->usageModel
                ->hasConsumedTarget(
                    $membershipId,
                    $viewedUserId
                )
            ) {
                $this->usageModel
                    ->recordRepeatView(
                        $membershipId,
                        $viewedUserId,
                        $nowUtc
                    );

                $membershipUsed = $this
                    ->usageModel
                    ->consumedCount(
                        $membershipId
                    );

                $dailyUsed = $this
                    ->usageModel
                    ->consumedCountForDate(
                        $membershipId,
                        $usageDateIst
                    );

                $this->commitOrFail();

                return [
                    'consumedNewAllowance' =>
                    false,

                    'membershipUsed' =>
                    $membershipUsed,

                    'membershipLimit' =>
                    $membershipLimit,

                    'dailyUsed' =>
                    $dailyUsed,

                    'dailyLimit' =>
                    $dailyLimit,
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
                throw new MembershipProfileQuotaExceededException(
                    'Your membership Full Profile limit has been reached.'
                );
            }

            $dailyUsed = $this
                ->usageModel
                ->consumedCountForDate(
                    $membershipId,
                    $usageDateIst
                );

            if (
                $dailyUsed
                >= $dailyLimit
            ) {
                throw new MembershipProfileQuotaExceededException(
                    'Your daily Full Profile limit has been reached. '
                        . 'Please try again tomorrow.'
                );
            }

            if (
                !$this->usageModel
                    ->consume(
                        $membershipId,
                        $viewerUserId,
                        $viewedUserId,
                        $usageDateIst,
                        $nowUtc
                    )
            ) {
                throw new RuntimeException(
                    'The profile allowance could not be recorded.'
                );
            }

            $membershipUsed++;

            $dailyUsed++;

            $this->commitOrFail();

            return [
                'consumedNewAllowance' =>
                true,

                'membershipUsed' =>
                $membershipUsed,

                'membershipLimit' =>
                $membershipLimit,

                'dailyUsed' =>
                $dailyUsed,

                'dailyLimit' =>
                $dailyLimit,
            ];
        } catch (Throwable $exception) {
            $this->database
                ->transRollback();

            throw $exception;
        }
    }

    /**
     * Verify that the locked commercial membership is still usable.
     *
     * Runtime authorization must never depend on the expiry cron having run.
     * The timestamp is authoritative even when an expired row temporarily
     * remains marked ACTIVE before lifecycle housekeeping executes.
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
     * Complete the transaction only when CI4 reports no database failure.
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
