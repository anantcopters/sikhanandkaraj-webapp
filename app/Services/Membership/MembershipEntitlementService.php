<?php

declare(strict_types=1);

namespace App\Services\Membership;

/**
 * Central authority for membership-controlled product capabilities.
 *
 * Controllers and services should ask whether a member has a capability
 * instead of implementing repeated "paid vs free" conditionals.
 *
 * Aadhaar Verification, Report, Block and Interest actions are intentionally
 * membership-neutral and are available to both Free and Paid members.
 *
 * The remaining commercial capabilities are controlled by active membership.
 * Keeping all capability decisions here allows future plan differentiation
 * without changing every controller or feature service.
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

    public function __construct(
        private readonly MembershipService $membershipService
    ) {}

    /**
     * Resolve one product capability.
     *
     * Aadhaar Verification, Report, Block, Send Interest and Receive Interest
     * are intentionally available to both Free and Paid members.
     *
     * Full-profile access returning true here means only that the membership
     * tier permits the feature. Verified-profile, gender/interest, blocking,
     * moderation and quota rules belong to the later ProfileAccessPolicy.
     */
    public function can(
        int $userId,
        string $capability
    ): bool {
        $normalizedCapability = mb_strtoupper(
            trim($capability)
        );

        /*
         * These capabilities are intentionally membership-neutral.
         *
         * Returning true here grants only the product entitlement. The target
         * operation must still enforce its own authentication, ownership,
         * workflow-state, blocking, moderation and domain-specific rules.
         *
         * Aadhaar belongs here because identity verification is now available
         * to both Free and Paid members. MemberAadhaarService remains
         * responsible for validating whether the current Aadhaar workflow
         * state permits a new upload.
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

        /*
         * Commercial capabilities requiring an active Paid membership.
         */
        return in_array(
            $normalizedCapability,
            [
                self::CAPABILITY_ADVANCED_SEARCH,
                self::CAPABILITY_VIEW_FULL_PROFILE,
                self::CAPABILITY_CREATE_LIVE_INTRODUCTION,
                self::CAPABILITY_WATCH_LIVE_INTRODUCTION,
                self::CAPABILITY_SHORTLIST,
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
}
