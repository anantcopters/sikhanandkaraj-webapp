<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberVideoIntroductionModel;
use App\Services\Matchmaking\MemberInteractionService;
use DomainException;

/**
 * Central member-facing authorization policy for Live Introduction playback.
 *
 * Signed CloudFront URLs must NEVER be generated before this policy succeeds.
 *
 * Authorization order matters:
 *
 * 1. Live Introduction membership capability;
 * 2. common protected-profile relationship/moderation;
 * 3. approved/active video;
 * 4. owner-selected video visibility;
 * 5. membership-scoped Live Introduction quota;
 * 6. only then may the caller create a signed URL.
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

        private readonly MemberInteractionService
        $interactionService,

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
         * Live Introduction owns an independent membership capability.
         *
         * Do this before resolving video state so a member without the
         * capability cannot probe protected video information.
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
         *
         * This validates:
         *
         * - blocked relationship;
         * - globally hidden/report-moderated target;
         * - active viewer and target;
         * - Verified Profile state;
         * - matrimonial gender/Interest privacy;
         * - authoritative current paid membership.
         *
         * It deliberately does NOT require VIEW_FULL_PROFILE.
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
         * Membership architecture interprets it as "visible to an entitled
         * paid member"; it must NOT be implemented as plan-code PRO or
         * users.is_paid.
         *
         * The WATCH_LIVE_INTRODUCTION capability was already verified above.
         */
        if (
            $visibility
            === MemberVideoIntroductionModel::VISIBILITY_PRO
        ) {
            // Capability already verified.
        } elseif (
            $visibility
            === MemberVideoIntroductionModel::VISIBILITY_ACCEPTED_INTEREST
        ) {
            /*
             * VISIBLE_AFTER_ACCEPTED_INTEREST follows the same directional
             * Interest authority used by Full Profile access.
             *
             * Direction is always relative to the viewer.
             *
             * Therefore an accepted Interest is sufficient only when the
             * viewer originally sent that Interest and the profile owner
             * accepted it:
             *
             *     viewer -> owner -> ACCEPTED
             *
             * ACCEPTED_RECEIVED deliberately does not satisfy this rule.
             *
             * Do not replace this with MemberInterestModel::acceptedBetween()
             * because that broader pair-level check loses Interest direction
             * and would create a second interpretation of the Full Profile
             * privacy rule.
             */
            $relationship = $this
                ->interactionService
                ->interestRelationshipFor(
                    $viewerUserId,
                    $ownerUserId
                );

            if (
                (
                    $relationship['state']
                    ?? ''
                ) !== MemberInteractionService
                ::INTEREST_STATE_ACCEPTED_SENT
            ) {
                throw new DomainException(
                    'This Live Introduction is available '
                        . 'after your Interest is accepted.'
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
