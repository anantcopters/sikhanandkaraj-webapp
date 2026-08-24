<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Exceptions\MembershipProfileQuotaExceededException;
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

        $membershipLimit = max(
            0,
            (int) (
                $membership['profileViewLimit']
                ?? 0
            )
        );

        $dailyLimit = max(
            0,
            (int) (
                $membership['dailyProfileViewLimit']
                ?? 0
            )
        );

        if (
            $viewerUserId <= 0
            || $viewedUserId <= 0
            || $membershipId <= 0
            || $membershipLimit <= 0
            || $dailyLimit <= 0
        ) {
            throw new RuntimeException(
                'The membership profile allowance is unavailable.'
            );
        }

        $now = new \DateTimeImmutable(
            'now',
            new \DateTimeZone('UTC')
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
             * Serialize all quota consumption for this membership.
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
             * Re-check ownership while holding the lock.
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
