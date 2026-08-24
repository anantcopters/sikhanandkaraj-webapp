<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberInterestModel;
use App\Models\MemberProfileReportModel;
use App\Models\MemberVideoIntroductionModel;
use DomainException;

/**
 * Central member-facing authorization policy for Live Introduction playback.
 *
 * Signed CloudFront URLs must NEVER be generated before this policy succeeds.
 *
 * Authorization order matters:
 *
 * 1. paid Live Introduction capability;
 * 2. common protected-profile relationship;
 * 3. approved/active video;
 * 4. moderation/report safety;
 * 5. owner-selected video visibility;
 * 6. membership-scoped Live Introduction quota;
 * 7. only then may the caller create a signed URL.
 */
final class LiveIntroductionAccessPolicy
{
    public function __construct(
        private readonly MembershipEntitlementService
        $entitlementService,

        private readonly ProfileAccessPolicy
        $profileAccessPolicy,

        private readonly MemberVideoIntroductionModel
        $videoModel,

        private readonly MemberInterestModel
        $interestModel,

        private readonly MemberProfileReportModel
        $profileReportModel,

        private readonly MembershipLiveIntroductionUsageService
        $usageService
    ) {}

    /**
     * Authorize one viewer playback and record commercial usage.
     *
     * @return array{
     *     video:array<string, mixed>,
     *     membership:array<string, mixed>,
     *     usage:array<string, mixed>
     * }
     */
    public function authorizePlayback(
        int $viewerUserId,
        int $ownerUserId
    ): array {
        if (
            $viewerUserId <= 0
            || $ownerUserId <= 0
            || $viewerUserId === $ownerUserId
        ) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        /*
         * Live Introduction is explicitly membership controlled.
         *
         * Do this before resolving the video so Free members cannot probe
         * protected video state.
         */
        if (
            !$this->entitlementService
                ->canWatchLiveIntroduction(
                    $viewerUserId
                )
        ) {
            throw new DomainException(
                'A paid membership is required to watch '
                    . 'Live Introductions.'
            );
        }

        /*
         * Reuse the common protected-profile authorization boundary WITHOUT
         * consuming Full Profile quota.
         */
        $profileAccess = $this
            ->profileAccessPolicy
            ->authorizeProfileRelationship(
                $viewerUserId,
                $ownerUserId
            );

        $video = $this
            ->videoModel
            ->activeForMember(
                $ownerUserId
            );

        if (
            !is_array($video)
            || (
                $video['moderation_status']
                ?? ''
            ) !== MemberVideoIntroductionModel::STATUS_APPROVED
            || trim(
                (string) (
                    $video['playback_object_key']
                    ?? ''
                )
            ) === ''
        ) {
            throw new DomainException(
                'This Live Introduction is not available.'
            );
        }

        /*
         * A profile hidden globally after Admin report review must not remain
         * accessible through its video endpoint.
         */
        if (
            $this->profileReportModel
            ->isGloballyHidden(
                $ownerUserId
            )
        ) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        $visibility = mb_strtoupper(
            trim(
                (string) (
                    $video['visibility']
                    ?? ''
                )
            )
        );

        if (
            $visibility
            === MemberVideoIntroductionModel::VISIBILITY_HIDDEN
        ) {
            throw new DomainException(
                'This member has hidden their Live Introduction.'
            );
        }

        /*
         * VISIBLE_PRO is the historical persisted value.
         *
         * Membership architecture now interprets it as "visible to an
         * entitled paid member"; it must NOT be implemented as plan-code PRO
         * or users.is_paid.
         *
         * GO, PLUS and PRO currently all own WATCH_LIVE_INTRODUCTION.
         */
        if (
            $visibility
            === MemberVideoIntroductionModel::VISIBILITY_PRO
        ) {
            // Membership capability was already verified above.
        } elseif (
            $visibility
            === MemberVideoIntroductionModel::VISIBILITY_ACCEPTED_INTEREST
        ) {
            /*
             * Video-specific owner privacy is stricter than the common
             * profile relationship when configured this way.
             */
            if (
                !$this->interestModel
                    ->acceptedBetween(
                        $viewerUserId,
                        $ownerUserId
                    )
            ) {
                throw new DomainException(
                    'This Live Introduction is available '
                        . 'after an Interest is accepted.'
                );
            }
        } else {
            /*
             * Unknown visibility values fail closed.
             */
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        $videoId = max(
            0,
            (int) (
                $video['id']
                ?? 0
            )
        );

        if ($videoId <= 0) {
            throw new DomainException(
                'Video playback is not available.'
            );
        }

        /*
         * Consume only after every authorization/privacy rule succeeds.
         */
        $usage = $this
            ->usageService
            ->recordAuthorizedView(
                $viewerUserId,
                $ownerUserId,
                $videoId,
                $profileAccess['membership']
            );

        return [
            'video' =>
            $video,

            'membership' =>
            $profileAccess['membership'],

            'usage' =>
            $usage,
        ];
    }
}
