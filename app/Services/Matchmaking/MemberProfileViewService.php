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
use App\Services\PartnerPreference\BasicPartnerPreferenceService;
use App\Services\PartnerPreference\AdditionalPartnerPreferenceService;
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

        private readonly BasicPartnerPreferenceService
        $basicPartnerPreferenceService,

        private readonly AdditionalPartnerPreferenceService
        $additionalPartnerPreferenceService,

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
        * Female contact presentation:
        *
        * When a parent contact exists:
        *
        * - show the female member's mobile in masked form;
        * - retain the verified badge when her primary mobile is OTP-verified;
        * - show the full parent contact beneath it.
        *
        * When a parent contact does not exist:
        *
        * - display the female member's full primary mobile.
        *
        * Male profiles continue displaying their normal primary mobile.
        */
        $hasParentContact =
            $isFemale
            && $parentContactNumber !== '';

        $maskedMemberMobile =
            $hasParentContact
            && $memberMobileNumber !== ''
            ? MobileNumberMasker::lastThree(
                $memberMobileNumber
            )
            : '';

        $displayMobileNumber =
            $hasParentContact
            ? $parentContactNumber
            : $memberMobileNumber;

        $displayMobileLabel =
            $hasParentContact
            ? 'Parents Mobile Number'
            : 'Mobile Number';

        /*
        * The displayed full number is verified only when it is the
        * member's primary mobile.
        *
        * When a parent contact is displayed, the separate masked member
        * number carries the member-mobile verification state.
        */
        $isDisplayMobileVerified =
            !$hasParentContact
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
            isset($partnerPreferenceMatch['criteria'])
            && is_array($partnerPreferenceMatch['criteria'])
            ? $partnerPreferenceMatch['criteria']
            : [];

        $basicPreferenceSummary =
            $this
            ->basicPartnerPreferenceService
            ->getSummaryForUser(
                $viewerUserId
            );

        $additionalPreferenceSections =
            $this
            ->additionalPartnerPreferenceService
            ->getSummarySections(
                $viewerUserId
            );

        /*
        * First build one lookup containing every available
        * human-readable Partner Preference summary item.
        *
        * Do NOT filter on isCompleted here.
        *
        * Whether a preference participates in matchmaking is already
        * determined by PartnerPreferenceMatchService and represented
        * by $matchCriteria.
        */
        $displayItemsByKey = [];

        $basicItems =
            isset($basicPreferenceSummary['items'])
            && is_array($basicPreferenceSummary['items'])
            ? $basicPreferenceSummary['items']
            : [];

        foreach ($basicItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = trim(
                (string) (
                    $item['key']
                    ?? ''
                )
            );

            if ($key === '') {
                continue;
            }

            $displayItemsByKey[$key] = [
                'key' =>
                $key,

                'title' =>
                trim(
                    (string) (
                        $item['title']
                        ?? ''
                    )
                ),

                'value' =>
                trim(
                    (string) (
                        $item['value']
                        ?? ''
                    )
                ),
            ];
        }

        foreach (
            $additionalPreferenceSections
            as $section
        ) {
            if (!is_array($section)) {
                continue;
            }

            $sectionItems =
                isset($section['items'])
                && is_array($section['items'])
                ? $section['items']
                : [];

            foreach ($sectionItems as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $key = trim(
                    (string) (
                        $item['key']
                        ?? ''
                    )
                );

                if (
                    $key === ''
                    || $key === 'special-request'
                ) {
                    continue;
                }

                $displayItemsByKey[$key] = [
                    'key' =>
                    $key,

                    'title' =>
                    trim(
                        (string) (
                            $item['title']
                            ?? ''
                        )
                    ),

                    'value' =>
                    trim(
                        (string) (
                            $item['value']
                            ?? ''
                        )
                    ),
                ];
            }
        }

        /*
        * Now construct the modal rows FROM THE MATCH CRITERIA.
        *
        * This guarantees that:
        *
        * total rows in modal
        *      ===
        * total preferences used by matching
        *
        * and therefore the modal can never silently show only a subset
        * because a presentation service used a different completion rule.
        */
        $partnerPreferenceDisplayItems = [];

        foreach ($matchCriteria as $criterion) {
            if (!is_array($criterion)) {
                continue;
            }

            $key = trim(
                (string) (
                    $criterion['key']
                    ?? ''
                )
            );

            if ($key === '') {
                continue;
            }

            $displayItem =
                $displayItemsByKey[$key]
                ?? null;

            if (!is_array($displayItem)) {
                continue;
            }

            $title = trim(
                (string) (
                    $displayItem['title']
                    ?? ''
                )
            );

            $value = trim(
                (string) (
                    $displayItem['value']
                    ?? ''
                )
            );

            if ($title === '') {
                continue;
            }

            /*
            * A configured match criterion should normally always have
            * a presentation value. Keep a safe member-friendly fallback
            * instead of silently removing the row from the modal.
            */
            if (
                $value === ''
                || $value === 'Not added'
            ) {
                $value = 'Preference selected';
            }

            $partnerPreferenceDisplayItems[] = [
                'key' =>
                $key,

                'title' =>
                $title,

                'value' =>
                $value,

                'matched' => (
                    $criterion['matched']
                    ?? false
                ) === true,

                'isCompulsory' => (
                    $criterion['compulsory']
                    ?? false
                ) === true,
            ];
        }

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
        $authorizedProfileImage =
            $this
            ->photoUrlService
            ->getApprovedPrimaryUrlForViewer(
                memberId: $targetUserId,

                viewerUserId: $viewerUserId,

                hasInterestRelationship: $hasInterestRelationship,

                variant: 'medium'
            );

        $summary['profileImage'] =
            $authorizedProfileImage;

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
                $hasParentContact
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
                $hasParentContact,

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
