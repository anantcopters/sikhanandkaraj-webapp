<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\UserModel;
use App\Services\Profile\MemberPhotoUrlService;
use App\Services\Profile\MemberProfileSummaryService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Produces an authorized profile view for another member.
 */
final class MemberProfileViewService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberProfileSummaryService $profileSummaryService,
        private readonly MemberPhotoUrlService $photoUrlService,
        private readonly MemberInteractionService $interactionService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profileForViewer(
        int $viewerUserId,
        string $profileReference
    ): array {
        $target = $this
            ->userModel
            ->findActiveByProfileReference(
                $profileReference
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
            || $targetUserId ===
            $viewerUserId
        ) {
            throw PageNotFoundException
                ::forPageNotFound();
        }

        /*
         * Do not reveal whether the target exists when either member
         * has blocked the other.
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

        $hasAnyInterest =
            $this
            ->interactionService
            ->hasInterestBetween(
                $viewerUserId,
                $targetUserId
            );

        $summary = $this
            ->profileSummaryService
            ->getForUser(
                $targetUserId
            );

        /*
         * Never trust the owner-oriented image URL returned by the shared
         * summary service when displaying someone else's profile.
         *
         * Replace it with a viewer-authorized medium URL.
         */
        $summary['profileImage'] =
            $this
            ->photoUrlService
            ->getApprovedPrimaryUrlForViewer(
                memberId: $targetUserId,

                viewerUserId: $viewerUserId,

                allowInterestedOnly: $hasAnyInterest,

                variant: 'medium'
            );

        /*
         * Do not expose gallery/original URLs through the existing
         * owner-only gallery endpoint.
         *
         * A viewer-authorized gallery can be added later.
         */
        $summary['approvedPhotos'] = [];

        /*
         * Count the successful authorized view.
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
                'viewedMemberId' =>
                $targetUserId,

                'viewedProfileReference' =>
                (string) $target['profile_ref_number'],

                'hasShownInterest' =>
                $this
                    ->interactionService
                    ->hasShownInterest(
                        $viewerUserId,
                        $targetUserId
                    ),
            ]
        );
    }

    /**
     * Resolve a visible target without recording a profile view.
     *
     * @return array<string, mixed>
     */
    public function targetForAction(
        int $viewerUserId,
        string $profileReference
    ): array {
        $target = $this
            ->userModel
            ->findActiveByProfileReference(
                $profileReference
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
            || $targetUserId ===
            $viewerUserId
            || $this
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
