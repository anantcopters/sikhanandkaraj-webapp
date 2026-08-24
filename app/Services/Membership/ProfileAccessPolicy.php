<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\UserModel;
use App\Services\Matchmaking\MemberInteractionService;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;

/**
 * Central authorization policy for protected another-member resources.
 *
 * Two concepts are deliberately separated:
 *
 * authorizeProfileRelationship()
 *     Verifies that the viewer is commercially/privacy-authorized to access
 *     protected information belonging to the target.
 *
 * authorizeFullProfile()
 *     Reuses the relationship authorization and additionally records Full
 *     Profile commercial usage.
 *
 * This separation is important because Live Introduction has its OWN
 * purchased allowance and therefore must not consume a Full Profile allowance
 * merely because the video belongs to that profile.
 */
final class ProfileAccessPolicy
{
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MembershipService
        $membershipService,

        private readonly MembershipEntitlementService
        $entitlementService,

        private readonly VerifiedProfilePolicy
        $verifiedProfilePolicy,

        private readonly MembershipProfileUsageService
        $usageService,

        private readonly MemberInteractionService
        $interactionService
    ) {}

    /**
     * Authorize protected access to another member without consuming Full
     * Profile quota.
     *
     * Live Introduction playback uses this method before applying its own
     * video-specific visibility and quota rules.
     *
     * @return array{
     *     viewer:array<string, mixed>,
     *     target:array<string, mixed>,
     *     membership:array<string, mixed>,
     *     verification:array<string, mixed>
     * }
     */
    public function authorizeProfileRelationship(
        int $viewerUserId,
        int $targetUserId
    ): array {
        if (
            $viewerUserId <= 0
            || $targetUserId <= 0
            || $viewerUserId === $targetUserId
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * Blocked relationships are never exposed through protected member
         * resources.
         *
         * This check occurs before membership and quota work so a blocked
         * request can never consume commercial allowance.
         */
        if (
            $this->interactionService
            ->isBlockedBetween(
                $viewerUserId,
                $targetUserId
            )
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * Protected another-member resources require a paid membership.
         *
         * Feature-specific capability checks may additionally be performed by
         * the caller. This establishes the common Full Profile relationship
         * boundary.
         */
        if (
            !$this->entitlementService
                ->canViewFullProfile(
                    $viewerUserId
                )
        ) {
            throw new DomainException(
                'A paid membership is required to access this profile.'
            );
        }

        $viewer = $this->userModel
            ->find(
                $viewerUserId
            );

        $target = $this->userModel
            ->find(
                $targetUserId
            );

        if (
            !is_array($viewer)
            || !is_array($target)
            || (
                $viewer['account_status']
                ?? ''
            ) !== UserModel::STATUS_ACTIVE
            || (
                $target['account_status']
                ?? ''
            ) !== UserModel::STATUS_ACTIVE
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * Verified Profile is a candidate property, not a membership property.
         *
         * A Free target whose Mobile, Email, Aadhaar or approved Live
         * Introduction supplies qualifying verification can therefore still
         * be a Verified Profile.
         */
        $verification = $this
            ->verifiedProfilePolicy
            ->stateForUser(
                $targetUserId
            );

        if (
            (
                $verification['isVerifiedProfile']
                ?? false
            ) !== true
        ) {
            throw new DomainException(
                'This profile is unavailable because the member '
                    . 'does not currently have a verified credential.'
            );
        }

        /*
         * Apply the matrimonial privacy rule before any sensitive resource is
         * exposed.
         */
        $this->assertGenderPrivacy(
            $viewerUserId,
            $targetUserId,
            (string) (
                $viewer['gender']
                ?? ''
            ),
            (string) (
                $target['gender']
                ?? ''
            )
        );

        $membershipState = $this
            ->membershipService
            ->resolveForUser(
                $viewerUserId
            );

        $membership = $membershipState['membership'] ?? null;

        if (!is_array($membership)) {
            /*
             * Defensive fail closed.
             *
             * MembershipEntitlementService and MembershipService should agree,
             * but protected data must never rely on that assumption.
             */
            throw new DomainException(
                'An active paid membership is required.'
            );
        }

        return [
            'viewer' =>
            $viewer,

            'target' =>
            $target,

            'membership' =>
            $membership,

            'verification' =>
            $verification,
        ];
    }

    /**
     * Authorize and record one Full Profile opening.
     *
     * @return array{
     *     membership:array<string, mixed>,
     *     usage:array<string, mixed>,
     *     verification:array<string, mixed>
     * }
     */
    public function authorizeFullProfile(
        int $viewerUserId,
        int $targetUserId
    ): array {
        /*
         * All relationship/privacy rules are centralized above.
         *
         * Do not reproduce those checks inside Full Profile, PDF or video
         * controllers.
         */
        $access = $this
            ->authorizeProfileRelationship(
                $viewerUserId,
                $targetUserId
            );

        /*
         * Only a successfully authorized Full Profile consumes profile-view
         * allowance.
         */
        $usage = $this
            ->usageService
            ->recordAuthorizedView(
                $viewerUserId,
                $targetUserId,
                $access['membership']
            );

        return [
            'membership' =>
            $access['membership'],

            'usage' =>
            $usage,

            'verification' =>
            $access['verification'],
        ];
    }

    /**
     * Enforce the matrimonial gender / accepted-Interest privacy rule.
     */
    private function assertGenderPrivacy(
        int $viewerUserId,
        int $targetUserId,
        string $viewerGender,
        string $targetGender
    ): void {
        $viewerGender = $this
            ->normalizeGender(
                $viewerGender
            );

        $targetGender = $this
            ->normalizeGender(
                $targetGender
            );

        /*
         * Female paid member -> male profile:
         *
         * No accepted Interest is required.
         */
        if (
            $viewerGender === 'F'
            && $targetGender === 'M'
        ) {
            return;
        }

        /*
         * Male paid member -> female profile:
         *
         * The male must have sent the Interest and the female must have
         * accepted it.
         */
        if (
            $viewerGender === 'M'
            && $targetGender === 'F'
        ) {
            $relationship = $this
                ->interactionService
                ->interestRelationshipFor(
                    $viewerUserId,
                    $targetUserId
                );

            if (
                (
                    $relationship['state']
                    ?? ''
                ) === MemberInteractionService
                ::INTEREST_STATE_ACCEPTED_SENT
            ) {
                return;
            }

            throw new DomainException(
                'This profile will become available after '
                    . 'the member accepts your Interest.'
            );
        }

        /*
         * Current matrimonial matching expects opposite-gender candidates.
         * Unknown or unsupported data fails closed.
         */
        throw new DomainException(
            'This profile is currently unavailable.'
        );
    }

    /**
     * Normalize current and defensive historical gender values.
     */
    private function normalizeGender(
        string $gender
    ): string {
        return match (mb_strtoupper(
            trim($gender)
        )) {
            'M',
            'MALE' =>
            'M',

            'F',
            'FEMALE' =>
            'F',

            default =>
            '',
        };
    }
}
