<?php

declare(strict_types=1);

namespace App\Services\Membership;

/**
 * Central authority for membership-controlled product capabilities.
 */
final class MembershipEntitlementService
{
    public const CAPABILITY_ADVANCED_SEARCH =
    'ADVANCED_SEARCH';

    public const CAPABILITY_VIEW_FULL_PROFILE =
    'VIEW_FULL_PROFILE';

    public const CAPABILITY_AADHAAR =
    'AADHAAR';

    public const CAPABILITY_CREATE_LIVE_INTRODUCTION =
    'CREATE_LIVE_INTRODUCTION';

    public const CAPABILITY_WATCH_LIVE_INTRODUCTION =
    'WATCH_LIVE_INTRODUCTION';

    public const CAPABILITY_SHORTLIST =
    'SHORTLIST';

    public const CAPABILITY_REPORT =
    'REPORT';

    public const CAPABILITY_BLOCK =
    'BLOCK';

    public const CAPABILITY_SEND_INTEREST =
    'SEND_INTEREST';

    public const CAPABILITY_RECEIVE_INTEREST =
    'RECEIVE_INTEREST';

    /*
     * Messaging.
     *
     * Receiving/reading is available to Free and Paid members.
     * Manual sending/replying requires an active Paid membership.
     */
    public const CAPABILITY_RECEIVE_MESSAGE =
    'RECEIVE_MESSAGE';

    public const CAPABILITY_SEND_MESSAGE =
    'SEND_MESSAGE';

    public function __construct(
        private readonly MembershipService $membershipService
    ) {}

    public function can(
        int $userId,
        string $capability
    ): bool {
        $normalizedCapability = mb_strtoupper(
            trim($capability)
        );

        /*
         * Membership-neutral capabilities.
         *
         * Domain-specific authorization still remains with the service which
         * performs the actual operation.
         */
        if (
            in_array(
                $normalizedCapability,
                [
                    self::CAPABILITY_AADHAAR,
                    self::CAPABILITY_REPORT,
                    self::CAPABILITY_BLOCK,
                    self::CAPABILITY_SEND_INTEREST,
                    self::CAPABILITY_RECEIVE_INTEREST,
                    self::CAPABILITY_RECEIVE_MESSAGE,
                ],
                true
            )
        ) {
            return true;
        }

        $membership = $this
            ->membershipService
            ->resolveForUser(
                $userId
            );

        if (
            ($membership['isPaid'] ?? false)
            !== true
        ) {
            return false;
        }

        return in_array(
            $normalizedCapability,
            [
                self::CAPABILITY_ADVANCED_SEARCH,
                self::CAPABILITY_VIEW_FULL_PROFILE,
                self::CAPABILITY_CREATE_LIVE_INTRODUCTION,
                self::CAPABILITY_WATCH_LIVE_INTRODUCTION,
                self::CAPABILITY_SHORTLIST,
                self::CAPABILITY_SEND_MESSAGE,
            ],
            true
        );
    }

    public function canUseAdvancedSearch(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_ADVANCED_SEARCH
        );
    }

    public function canViewFullProfile(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_VIEW_FULL_PROFILE
        );
    }

    public function canUseAadhaar(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_AADHAAR
        );
    }

    public function canCreateLiveIntroduction(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_CREATE_LIVE_INTRODUCTION
        );
    }

    public function canWatchLiveIntroduction(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_WATCH_LIVE_INTRODUCTION
        );
    }

    public function canShortlist(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_SHORTLIST
        );
    }

    public function canReport(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_REPORT
        );
    }

    public function canBlock(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_BLOCK
        );
    }

    public function canSendInterest(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_SEND_INTEREST
        );
    }

    public function canReceiveInterest(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_RECEIVE_INTEREST
        );
    }

    public function canReceiveMessage(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_RECEIVE_MESSAGE
        );
    }

    public function canSendMessage(
        int $userId
    ): bool {
        return $this->can(
            $userId,
            self::CAPABILITY_SEND_MESSAGE
        );
    }
}
