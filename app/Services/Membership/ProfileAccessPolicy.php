<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberProfileReportModel;
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
 *     Verifies that the viewer is relationship/privacy-authorized to access
 *     protected information belonging to the target.
 *
 * authorizeFullProfile()
 *     Verifies the Full Profile membership capability, reuses the common
 *     relationship authorization and additionally records Full Profile
 *     commercial usage.
 *
 * This separation is important because protected resources such as Live
 * Introduction own independent membership capabilities and allowances.
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
        $interactionService,

        private readonly MemberProfileReportModel
        $profileReportModel
    ) {}

    /**
     * Authorize the common protected relationship to another member without
     * consuming Full Profile quota.
     *
     * IMPORTANT:
     *
     * This method deliberately does NOT check VIEW_FULL_PROFILE or
     * WATCH_LIVE_INTRODUCTION.
     *
     * Feature-specific membership capabilities belong to the caller.
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
         * Admin-moderated globally hidden profiles must remain unavailable
         * through every protected member resource.
         *
         * Discovery filtering is not an authorization boundary because a
         * member may reach Full Profile, PDF or Live Introduction directly.
         */
        if (
            $this->profileReportModel
            ->isGloballyHidden(
                $targetUserId
            )
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
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

        /*
         * Resolve the authoritative current paid membership.
         *
         * Feature capability has already been checked by the caller:
         *
         * - authorizeFullProfile() checks VIEW_FULL_PROFILE;
         * - LiveIntroductionAccessPolicy checks WATCH_LIVE_INTRODUCTION.
         *
         * The membership snapshot is still required because the feature's
         * usage service records allowance against the purchased membership.
         */
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
             * A feature entitlement and MembershipService should agree, but
             * protected data must never rely on that assumption.
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
         * Full Profile entitlement belongs specifically to Full Profile.
         *
         * Do not move this check into authorizeProfileRelationship() because
         * Live Introduction reuses that relationship boundary while owning a
         * separate WATCH_LIVE_INTRODUCTION capability and allowance.
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

        /*
         * All common relationship/privacy/moderation rules are centralized
         * above.
         *
         * Do not reproduce those checks inside Full Profile or PDF
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
