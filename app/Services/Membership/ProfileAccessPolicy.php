<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberInterestModel;
use App\Models\UserModel;
use App\Services\Matchmaking\MemberInteractionService;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;

/**
 * Central authorization policy for another member's Full Profile.
 *
 * This class decides whether the profile may be opened.
 *
 * It does NOT load sensitive profile details and it does NOT itself mutate
 * usage. Consumption is delegated to MembershipProfileUsageService only
 * after every access rule has passed.
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
     * Authorize and consume/record one Full Profile opening.
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
        if (
            $viewerUserId <= 0
            || $targetUserId <= 0
            || $viewerUserId === $targetUserId
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * The common interaction layer already owns block semantics.
         *
         * Do this before membership/quota work so blocked profiles never
         * consume commercial allowance.
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
         * Free members may discover Profile Cards but may not open another
         * member's Full Profile.
         */
        if (
            !$this->entitlementService
                ->canViewFullProfile(
                    $viewerUserId
                )
        ) {
            throw new DomainException(
                'A paid membership is required to view the Full Profile.'
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
                $target['account_status']
                ?? ''
            ) !== UserModel::STATUS_ACTIVE
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * Verified Profile is a candidate property, NOT a membership property.
         *
         * A Free candidate whose Mobile is verified therefore qualifies.
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
                'This Full Profile is unavailable because the member '
                    . 'does not currently have a verified credential.'
            );
        }

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
             * Defensive fail-closed check.
             *
             * Entitlement and membership resolution should agree, but access
             * to sensitive profile data must never depend on that assumption.
             */
            throw new DomainException(
                'An active paid membership is required to view this profile.'
            );
        }

        /*
         * Usage is recorded only after:
         *
         * - paid entitlement;
         * - active target;
         * - Verified Profile;
         * - gender/interest privacy;
         * - block safety
         *
         * have all succeeded.
         */
        $usage = $this
            ->usageService
            ->recordAuthorizedView(
                $viewerUserId,
                $targetUserId,
                $membership
            );

        return [
            'membership' =>
            $membership,

            'usage' =>
            $usage,

            'verification' =>
            $verification,
        ];
    }

    /**
     * Enforce the matrimonial gender/interest Full Profile rule.
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
         * Female paid member viewing a male profile:
         *
         * Full Profile is available without requiring an accepted Interest.
         */
        if (
            $viewerGender === 'F'
            && $targetGender === 'M'
        ) {
            return;
        }

        /*
         * Male paid member viewing a female profile:
         *
         * The female member must have ACCEPTED this male member's Interest.
         *
         * The relationship service returns state relative to the viewer.
         * ACCEPTED_SENT therefore means:
         *
         * male viewer sent Interest -> female target accepted it.
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
                'This Full Profile will become available after '
                    . 'the member accepts your Interest.'
            );
        }

        /*
         * Matrimonial matching currently expects opposite-gender profiles.
         * Unknown/unsupported data must fail closed instead of accidentally
         * exposing Full Profile information.
         */
        throw new DomainException(
            'This Full Profile is currently unavailable.'
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
