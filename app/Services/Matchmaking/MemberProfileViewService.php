<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\UserModel;
use App\Services\Profile\MemberPhotoUrlService;
use App\Models\UserContactModel;
use App\Support\MemberNameVisibility;
use App\Support\BooleanValue;
use App\Services\Profile\MemberProfileSummaryService;
use App\Models\MemberProfileReportModel;
use App\Support\EmailAddressMasker;
use App\Services\Membership\ProfileAccessPolicy;
use App\Support\MobileNumberMasker;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Builds an authorized profile view of one member for another member.
 *
 * Security order:
 *
 * 1. Resolve active target by public reference.
 * 2. Reject self access through other-member route.
 * 3. Reject a block in either direction.
 * 4. Load profile data without creating owner-context media URLs.
 * 5. Resolve interest relationship.
 * 6. Enforce photo visibility.
 * 7. Generate one authorized medium URL.
 * 8. Record the successful profile view.
 */
final class MemberProfileViewService
{
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly UserContactModel
        $userContactModel,

        private readonly MemberProfileSummaryService
        $profileSummaryService,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly MemberInteractionService
        $interactionService,

        private readonly MemberProfileReportModel
        $profileReportModel,

        private readonly MemberMatchmakingService
        $matchmakingService,

        private readonly PartnerPreferencePresentationService
        $partnerPreferencePresentationService,

        private readonly ProfileAccessPolicy
        $profileAccessPolicy
    ) {}

    /**
     * Return one another-member profile authorized for the viewer.
     *
     * @return array<string, mixed>
     */
    public function profileForViewer(
        int $viewerUserId,
        string $profileReference
    ): array {
        $target = $this
            ->resolveVisibleTarget(
                $viewerUserId,
                $profileReference
            );

        $targetUserId = max(
            0,
            (int) (
                $target['id']
                ?? 0
            )
        );

        /*
        * Full Profile authorization happens BEFORE sensitive profile information,
        * contact details, Aadhaar data or signed media URLs are resolved.
        *
        * ProfileAccessPolicy centrally owns:
        *
        * - paid membership entitlement;
        * - Verified Profile requirement;
        * - female accepted-interest privacy;
        * - block protection;
        * - membership-wide quota;
        * - daily quota;
        * - repeat-view consumption.
        *
        * No Full Profile authorization rule should be duplicated below this point.
        */
        $profileAccess = $this
            ->profileAccessPolicy
            ->authorizeFullProfile(
                $viewerUserId,
                $targetUserId
            );



        /*
        * Load profile information without resolving an owner-context
        * signed image.
        *
        * Family Details is required here because female profiles prefer
        * the saved parent contact number.
        */
        $summary = $this
            ->profileSummaryService
            ->getForUser(
                $targetUserId,
                false
            );

        $familyDetails =
            isset($summary['familyDetails'])
            && is_array($summary['familyDetails'])
            ? $summary['familyDetails']
            : [];

        /*
        * Contacts are resolved server-side from the member's primary
        * contact records.
        */
        $mobileContact = $this
            ->userContactModel
            ->findPrimaryForUser(
                $targetUserId,
                UserContactModel::TYPE_MOBILE
            );

        $emailContact = $this
            ->userContactModel
            ->findPrimaryForUser(
                $targetUserId,
                UserContactModel::TYPE_EMAIL
            );

        $memberMobileNumber =
            is_array($mobileContact)
            ? trim(
                (string) (
                    $mobileContact['contact_value']
                    ?? ''
                )
            )
            : '';

        $emailAddress =
            is_array($emailContact)
            ? trim(
                (string) (
                    $emailContact['contact_value']
                    ?? ''
                )
            )
            : '';

        $maskedEmailAddress =
            EmailAddressMasker::mask(
                $emailAddress
            );

        $isMemberEmailVerified =
            is_array($emailContact)
            && $emailAddress !== ''
            && BooleanValue::fromDatabase(
                $emailContact['is_verified']
                    ?? false
            );

        $isMemberMobileVerified =
            is_array($mobileContact)
            && BooleanValue::fromDatabase(
                $mobileContact['is_verified']
                    ?? false
            );

        /*
        * Gender is currently stored by registration as:
        *
        * M = Male
        * F = Female
        *
        * FEMALE is also accepted defensively for older/imported data.
        */
        $gender = mb_strtoupper(
            trim(
                (string) (
                    $target['gender']
                    ?? $summary['user']['gender']
                    ?? ''
                )
            )
        );

        $isFemale = in_array(
            $gender,
            [
                'F',
                'FEMALE',
            ],
            true
        );

        $parentContactNumber = trim(
            (string) (
                $familyDetails['parent_contact_number']
                ?? ''
            )
        );

        /*
        * Female contact privacy:
        *
        * A female member's primary mobile number must never be exposed
        * as the full contact number on a member-facing profile.
        *
        * Female profile:
        *
        * - parent mobile is the primary displayed contact when available;
        * - member mobile is displayed only in masked form;
        * - absence of a parent mobile must never fall back to the
        *   female member's full mobile number.
        *
        * Male profile:
        *
        * - primary member mobile continues to be displayed normally.
        */
        $hasParentContact =
            $isFemale
            && $parentContactNumber !== '';

        $maskedMemberMobile =
            $isFemale
            && $memberMobileNumber !== ''
            ? MobileNumberMasker::lastThree(
                $memberMobileNumber
            )
            : '';

        $displayMobileNumber =
            $isFemale
            ? $parentContactNumber
            : $memberMobileNumber;

        $displayMobileLabel =
            $isFemale
            ? 'Parents Mobile Number'
            : 'Mobile Number';

        /*
        * Verification belongs to the member's primary mobile.
        *
        * Parent mobile is not represented as OTP-verified unless a
        * separate parent-contact verification mechanism exists.
        */
        $isDisplayMobileVerified =
            !$isFemale
            && $isMemberMobileVerified;

        /*
        * The other-member Full Profile view must use the same female-name
        * privacy rule as all multi-profile presentation components.
        *
        * This route rejects self-access, so the logged-in member is always
        * viewing somebody else's profile. Admin and Super Admin use their
        * separate management service and are not affected by this mutation.
        */
        if (
            isset($summary['user'])
            && is_array($summary['user'])
        ) {
            $summary['user']['full_name'] =
                MemberNameVisibility::forDisplay(
                    fullName: $summary['user']['full_name']
                        ?? '',

                    gender: $summary['user']['gender']
                        ?? $target['gender']
                        ?? '',

                    canViewFullName: false
                );
        }

        /*
        * INTERESTED_MEMBERS visibility is satisfied by an interest
        * in either direction.
        */
        $hasInterestRelationship =
            $this
            ->interactionService
            ->hasInterestBetween(
                $viewerUserId,
                $targetUserId
            );

        /*
        * Resolve the complete Interest relationship once.
        *
        * The View should never interpret directional database rows or
        * derive business state itself.
        */
        $interestRelationship =
            $this
            ->interactionService
            ->interestRelationshipFor(
                $viewerUserId,
                $targetUserId
            );

        /*
        * Calculate the logged-in member against the viewed member's
        * Partner Preferences.
        *
        * Direction:
        *
        * viewed member preferences -> logged-in member profile
        */
        $partnerPreferenceMatch =
            $this
            ->matchmakingService
            ->profilePreferenceMatch(
                $viewerUserId,
                $targetUserId
            );

        /*
        * Build presentation values for exactly the preferences used by
        * PartnerPreferenceMatchService.
        *
        * IMPORTANT:
        *
        * The matching service is the source of truth for which criteria
        * are configured and therefore included in the match count.
        *
        * BasicPartnerPreferenceService and
        * AdditionalPartnerPreferenceService are used only to resolve
        * those criteria into member-friendly labels.
        */
        $matchCriteria =
            isset(
                $partnerPreferenceMatch['criteria']
            )
            && is_array(
                $partnerPreferenceMatch['criteria']
            )
            ? $partnerPreferenceMatch['criteria']
            : [];

        $partnerPreferenceDisplayItems =
            $this
            ->partnerPreferencePresentationService
            ->displayItems(
                $viewerUserId,
                $matchCriteria
            );

        /*
        * Profile-detail pages use the MEDIUM variant.
        *
        * The photo service additionally checks:
        *
        * - approved;
        * - primary;
        * - valid visibility;
        * - PUBLIC or eligible INTERESTED_MEMBERS.
        */
        $authorizedPrimaryPhoto =
            $this
            ->photoUrlService
            ->getApprovedPrimaryPresentationForViewer(
                memberId: $targetUserId,

                viewerUserId: $viewerUserId,

                hasInterestRelationship: $hasInterestRelationship,

                variant: 'medium'
            );

        $authorizedProfileImage = trim(
            (string) (
                $authorizedPrimaryPhoto['url']
                ?? ''
            )
        );

        $summary['profileImage'] =
            $authorizedProfileImage;

        $summary['photoFocalX'] =
            max(
                0,
                min(
                    100,
                    (int) (
                        $authorizedPrimaryPhoto['focalX']
                        ?? 50
                    )
                )
            );

        $summary['photoFocalY'] =
            max(
                0,
                min(
                    100,
                    (int) (
                        $authorizedPrimaryPhoto['focalY']
                        ?? 20
                    )
                )
            );

        /*
        * getForUser(..., false) produced a summary without an image.
        * Keep downstream presentation metadata synchronized with the
        * viewer-authorized image.
        */
        if (
            isset(
                $summary['overallProfileSummary']
            )
            && is_array(
                $summary['overallProfileSummary']
            )
        ) {
            $summary['overallProfileSummary']['hasProfilePhoto'] =
                $authorizedProfileImage !== '';

            $summary['overallProfileSummary']['profilePhotoUrl'] =
                $authorizedProfileImage;
        }

        /*
        * Another-member gallery.
        *
        * Only viewer-authorized approved photos are returned:
        *
        * PUBLIC
        *     -> visible to any otherwise-authorized member.
        *
        * INTERESTED_MEMBERS
        *     -> requires an interest relationship in either direction.
        *
        * Thumbnail signed URLs only are returned in the initial profile
        * response.
        */
        $summary['approvedPhotos'] = $this
            ->photoUrlService
            ->getApprovedGalleryForViewer(
                memberId: $targetUserId,

                viewerUserId: $viewerUserId,

                hasInterestRelationship: $hasInterestRelationship
            );

        /*
        * Record a view only after the profile has successfully passed
        * visibility and block authorization.
        */
        $this
            ->interactionService
            ->recordView(
                $viewerUserId,
                $targetUserId
            );

        /*
        * Expose already-resolved membership usage to presentation.
        *
        * The View must never calculate quotas itself.
        */
        $summary['profileAccess'] =
            $profileAccess;

        return array_merge(
            $summary,
            [
                /*
             * This value remains server-side. URLs use the public
             * profile reference.
             */
                'viewedMemberId' =>
                $targetUserId,

                'viewedProfileReference' =>
                trim(
                    (string) (
                        $target['profile_ref_number']
                        ?? ''
                    )
                ),

                /*
             * Complete Interest presentation state.
             */
                'interestRelationship' =>
                $interestRelationship,

                /*
             * Retained temporarily for backward compatibility.
             */
                'hasShownInterest' => (
                    $interestRelationship['hasOutgoing']
                    ?? false
                ) === true,

                'hasInterestRelationship' =>
                $hasInterestRelationship,

                /*
                * Presentation-ready contact information.
                */
                'viewedMobile' =>
                $displayMobileNumber,

                'viewedMobileLabel' =>
                $displayMobileLabel,

                'viewedMaskedMemberMobile' =>
                $maskedMemberMobile,

                'isViewedMaskedMobileVerified' =>
                $isFemale
                    && $maskedMemberMobile !== ''
                    && $isMemberMobileVerified,

                'viewedEmail' =>
                $maskedEmailAddress,

                'isViewedEmailVerified' =>
                $maskedEmailAddress !== ''
                    && $isMemberEmailVerified,

                'isViewedMobileVerified' =>
                $isDisplayMobileVerified,

                'isViewedParentMobile' =>
                $isFemale
                    && $parentContactNumber !== '',

                'isShortlisted' =>
                $this
                    ->interactionService
                    ->hasShortlisted(
                        $viewerUserId,
                        $targetUserId
                    ),

                'partnerPreferenceMatch' =>
                $partnerPreferenceMatch,

                'partnerPreferenceDisplayItems' =>
                $partnerPreferenceDisplayItems,

            ]
        );
    }

    /**
     * Resolve a visible target for an interaction action.
     *
     * POSTing Show Interest or Block must not increment the profile-view
     * counter.
     *
     * @return array<string, mixed>
     */
    public function targetForAction(
        int $viewerUserId,
        string $profileReference
    ): array {
        return $this
            ->resolveVisibleTarget(
                $viewerUserId,
                $profileReference
            );
    }

    /**
     * Return one Full-Profile-authorized medium gallery photo URL.
     *
     * SECURITY:
     *
     * A medium gallery image is Full Profile detail media.
     *
     * Therefore a direct request for:
     *
     *     /members/{ref}/photos/{photoId}/medium-url
     *
     * must pass through the same authorization boundary as the Full Profile
     * itself. The endpoint must never become an alternate path around:
     *
     * - paid membership entitlement;
     * - Verified Profile requirement;
     * - male -> female accepted-Interest privacy;
     * - member-to-member blocking;
     * - administrator global-hide moderation;
     * - Full Profile membership quota;
     * - daily Full Profile quota.
     *
     * ProfileAccessPolicy remains the single authority for those rules.
     *
     * MembershipProfileUsageService treats an already-consumed target as a
     * repeat opening, so loading a medium image from an already-open Full
     * Profile does not consume another Full Profile allowance.
     */
    public function galleryMediumUrlForViewer(
        int $viewerUserId,
        string $profileReference,
        int $photoId
    ): string {
        if ($photoId <= 0) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * Resolve the target from the public profile reference.
         *
         * This also applies:
         *
         * - active-member validation;
         * - self-access protection;
         * - bidirectional block protection;
         * - administrator global-hide protection.
         *
         * Never accept another member's numeric database ID from the request.
         */
        $target = $this
            ->resolveVisibleTarget(
                $viewerUserId,
                $profileReference
            );

        $targetUserId = max(
            0,
            (int) (
                $target['id']
                ?? 0
            )
        );

        if ($targetUserId <= 0) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * SECURITY BOUNDARY
         * ------------------------------------------------------------------
         *
         * Medium gallery media belongs to the protected Full Profile.
         *
         * Do not replace this with targetForAction() authorization.
         * targetForAction() intentionally permits interactions such as
         * Show Interest without requiring Full Profile access.
         *
         * authorizeFullProfile() centrally enforces the commercial and
         * matrimonial privacy rules before any medium image URL is created.
         */
        $this
            ->profileAccessPolicy
            ->authorizeFullProfile(
                $viewerUserId,
                $targetUserId
            );

        /*
         * Photo visibility remains an additional restriction.
         *
         * Full Profile authorization answers:
         *
         *     "May this viewer access this member's Full Profile?"
         *
         * Photo visibility independently answers:
         *
         *     "May this particular approved photo be shown to this viewer?"
         *
         * PUBLIC
         *     -> visible after Full Profile authorization.
         *
         * INTERESTED_MEMBERS
         *     -> additionally requires an Interest relationship.
         */
        $hasInterestRelationship = $this
            ->interactionService
            ->hasInterestBetween(
                $viewerUserId,
                $targetUserId
            );

        return $this
            ->photoUrlService
            ->getApprovedGalleryMediumUrlForViewer(
                memberId: $targetUserId,
                viewerUserId: $viewerUserId,
                photoId: $photoId,
                hasInterestRelationship: $hasInterestRelationship
            );
    }

    /**
     * Resolve an active member which remains visible to this viewer.
     *
     * A generic 404 is intentional when the pair is blocked. Do not reveal
     * through a direct URL whether the other account still exists.
     *
     * @return array<string, mixed>
     */
    private function resolveVisibleTarget(
        int $viewerUserId,
        string $profileReference
    ): array {
        if ($viewerUserId <= 0) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $normalizedReference =
            mb_strtoupper(
                trim(
                    $profileReference
                )
            );

        if ($normalizedReference === '') {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $target = $this
            ->userModel
            ->findActiveByProfileReference(
                $normalizedReference
            );

        if (!is_array($target)) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        $targetUserId = max(
            0,
            (int) (
                $target['id']
                ?? 0
            )
        );

        if (
            $targetUserId <= 0
            || $targetUserId
            === $viewerUserId
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * Member-to-member blocking is bidirectional for visibility:
         *
         * A -> B
         * OR
         * B -> A
         *
         * means neither member can view the other.
         */
        if (
            $this
            ->interactionService
            ->isBlockedBetween(
                $viewerUserId,
                $targetUserId
            )
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        if (
            $this
            ->profileReportModel
            ->isGloballyHidden(
                $targetUserId
            )
        ) {
            /*
            * Return the same generic 404 used for blocked or unavailable profiles.
            * Do not reveal report/moderation information to another member.
            */
            throw PageNotFoundException
                ::forPageNotFound();
        }

        return $target;
    }
}
