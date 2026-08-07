<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\UserModel;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Profile\MemberProfileSummaryService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Builds an authorized profile view of one member for another member.
 *
 * This service owns the security sequence:
 *
 * 1. Resolve active target by public profile reference.
 * 2. Prevent self-view through the other-member route.
 * 3. Reject blocked relationships.
 * 4. Load profile data without signing owner-context media.
 * 5. Determine interest relationship.
 * 6. Apply photo visibility and generate an authorized medium URL.
 * 7. Record the successful profile view.
 */
final class MemberProfileViewService
{
    public function __construct(
        private readonly UserModel
        $userModel,

        private readonly MemberProfileSummaryService
        $profileSummaryService,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly MemberInteractionService
        $interactionService
    ) {}

    /**
     * Return another member's authorized profile.
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

        $targetUserId = (int) (
            $target['id']
            ?? 0
        );

        /*
         * IMPORTANT:
         *
         * Do not generate the normal owner-context profile-image URL.
         *
         * The target's photo visibility has not yet been evaluated for
         * this viewer.
         */
        $summary = $this
            ->profileSummaryService
            ->getForUser(
                $targetUserId,
                false
            );

        /*
         * INTERESTED_MEMBERS visibility is satisfied when an interest
         * exists in either direction.
         */
        $hasInterestRelationship =
            $this
            ->interactionService
            ->hasInterestBetween(
                $viewerUserId,
                $targetUserId
            );

        /*
         * Profile-detail page uses MEDIUM according to current media rules.
         *
         * No URL is generated when:
         *
         * - photo is not approved;
         * - no primary photo exists;
         * - visibility is invalid;
         * - visibility is INTERESTED_MEMBERS but there is no interest.
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

        /*
         * Replace the intentionally empty owner-context image with the
         * viewer-authorized image.
         */
        $summary['profileImage'] =
            $authorizedProfileImage;

        /*
         * getForUser(..., false) correctly built the summary without an
         * image. Now synchronize presentation metadata with the authorized
         * result so downstream views receive a consistent contract.
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
                $authorizedProfileImage
                !== '';

            $summary['overallProfileSummary']['profilePhotoUrl'] =
                $authorizedProfileImage;
        }

        /*
         * The existing member-owned approvedPhotos/gallery endpoint must
         * not be reused for another-member viewing.
         *
         * Until viewer-authorized gallery support is added, pass an empty
         * collection rather than leaking owner-authorized gallery URLs.
         */
        $summary['approvedPhotos'] = [];

        /*
         * Record only a successfully authorized profile view.
         *
         * Blocked/invalid/direct-URL attempts are rejected before this point
         * and therefore do not inflate profile-view counts.
         */
        $this
            ->interactionService
            ->recordView(
                $viewerUserId,
                $targetUserId
            );

        return array_merge(
            $summary,
            [
                /*
                 * Internal ID remains server-side. The controller/view
                 * should continue using profile_ref_number in member URLs.
                 */
                'viewedMemberId' =>
                $targetUserId,

                'viewedProfileReference' =>
                (string) (
                    $target['profile_ref_number']
                    ?? ''
                ),

                'hasShownInterest' =>
                $this
                    ->interactionService
                    ->hasShownInterest(
                        $viewerUserId,
                        $targetUserId
                    ),

                'hasInterestRelationship' =>
                $hasInterestRelationship,
            ]
        );
    }

    /**
     * Resolve a target for POST actions without recording a profile view.
     *
     * Interest/block actions must not increase profile-view counts.
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
     * Resolve an active target which remains visible to the viewer.
     *
     * A generic 404 is intentional for blocked pairs so the endpoint does
     * not reveal whether the profile still exists.
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

        $targetUserId = (int) (
            $target['id']
            ?? 0
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
         * Blocking is bidirectional for member visibility:
         *
         * A blocked B
         * OR
         * B blocked A
         *
         * => neither sees the other's profile.
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

        return $target;
    }
}
