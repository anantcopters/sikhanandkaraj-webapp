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

        private readonly MemberProfileSummaryService
        $profileSummaryService,

        private readonly MemberPhotoUrlService
        $photoUrlService,

        private readonly MemberInteractionService
        $interactionService
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
         * Point 26:
         *
         * Do NOT generate the normal owner-context signed medium URL.
         * Another-member authorization has not been evaluated yet.
         */
        $summary = $this
            ->profileSummaryService
            ->getForUser(
                $targetUserId,
                false
            );

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
         * Point 17:
         *
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
                $authorizedProfileImage
                !== '';

            $summary['overallProfileSummary']['profilePhotoUrl'] =
                $authorizedProfileImage;
        }

        /*
         * Existing gallery endpoints are owner-authorized.
         *
         * Do not reuse them for another member. A separate viewer-authorized
         * gallery workflow may be introduced later.
         */
        $summary['approvedPhotos'] = [];

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

        return $target;
    }
}
