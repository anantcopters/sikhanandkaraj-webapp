<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberMembershipModel;
use App\Models\MemberMatchScoringSignalModel;
use App\Models\MemberPhotoModel;
use App\Models\MemberVideoIntroductionModel;
use App\Models\UserContactModel;
use App\Models\UserModel;
use App\Services\Matchmaking\MatchScoreConfigurationService;
use App\Services\Matchmaking\MemberMatchScoreService;
use App\Support\BooleanValue;
use CodeIgniter\Database\BaseConnection;

/**
 * Read-only administrator diagnostics for intrinsic Match Score signals.
 *
 * IMPORTANT:
 *
 * This service deliberately does NOT manufacture a final Match Score.
 *
 * Final score requires viewer-specific Partner Preference compatibility.
 * Supplying match_percentage = 0 simply to obtain a "score" would create a
 * misleading administrative value.
 */
final class MemberMatchScoreDiagnosticService
{
    public function __construct(
        private readonly BaseConnection
        $database,

        private readonly MatchScoreConfigurationService
        $configurationService,

        private readonly MemberMatchScoreService
        $matchScoreService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forMember(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        /*
         * Build one compact diagnostic projection.
         *
         * These definitions mirror MemberMatchCandidateModel so Admin
         * diagnostics cannot drift from actual ranking inputs.
         */
        $activeStatus =
            $this->database->escape(
                MemberMembershipModel
                ::STATUS_ACTIVE
            );

        $approvedPhotoStatus =
            $this->database->escape(
                MemberPhotoModel
                ::STATUS_APPROVED
            );

        $approvedVideoStatus =
            $this->database->escape(
                MemberVideoIntroductionModel
                ::STATUS_APPROVED
            );

        $row =
            $this->database
            ->table(
                'users u'
            )
            ->select(
                '
                    u.id,
                    u.is_aadhaar_verified,

                    COALESCE(
                        scoring_signal.profile_completion,
                        0
                    ) AS profile_completion,

                    COALESCE(
                        candidate_photos.approved_photo_count,
                        0
                    ) AS approved_photo_count,

                    primary_mobile.is_verified
                        AS is_mobile_verified,

                    primary_email.is_verified
                        AS is_email_verified,

                    CASE
                        WHEN approved_video.id IS NOT NULL
                        THEN TRUE
                        ELSE FALSE
                    END AS is_video_introduction_verified,

                    active_membership.plan_code_snapshot
                        AS membership_plan_code,

                    active_membership.plan_name_snapshot
                        AS membership_plan_name,

                    COALESCE(
                        active_membership.commercial_priority_snapshot,
                        0
                    ) AS membership_commercial_priority
                    ',
                false
            )
            ->join(
                'member_match_scoring_signals scoring_signal',
                'scoring_signal.user_id = u.id',
                'left'
            )
            ->join(
                'LATERAL (
                        SELECT
                            COUNT(*)::INTEGER
                                AS approved_photo_count
                        FROM member_photos candidate_photo
                        WHERE candidate_photo.member_id = u.id
                        AND candidate_photo.status = '
                    . $approvedPhotoStatus
                    . '
                        AND candidate_photo.deleted_at IS NULL
                    ) candidate_photos',
                'TRUE',
                'left',
                false
            )
            ->join(
                'LATERAL (
                        SELECT
                            candidate_mobile.is_verified
                        FROM user_contacts candidate_mobile
                        WHERE candidate_mobile.user_id = u.id
                        AND candidate_mobile.contact_type = \'MOBILE\'
                        AND candidate_mobile.is_primary = TRUE
                        ORDER BY candidate_mobile.id DESC
                        LIMIT 1
                    ) primary_mobile',
                'TRUE',
                'left',
                false
            )
            ->join(
                'LATERAL (
                        SELECT
                            candidate_email.is_verified
                        FROM user_contacts candidate_email
                        WHERE candidate_email.user_id = u.id
                        AND candidate_email.contact_type = \'EMAIL\'
                        AND candidate_email.is_primary = TRUE
                        ORDER BY candidate_email.id DESC
                        LIMIT 1
                    ) primary_email',
                'TRUE',
                'left',
                false
            )
            ->join(
                'LATERAL (
                        SELECT
                            candidate_video.id
                        FROM member_video_introductions candidate_video
                        WHERE candidate_video.user_id = u.id
                        AND candidate_video.status = '
                    . $approvedVideoStatus
                    . '
                        ORDER BY
                            candidate_video.updated_at DESC,
                            candidate_video.id DESC
                        LIMIT 1
                    ) approved_video',
                'TRUE',
                'left',
                false
            )
            ->join(
                'LATERAL (
                        SELECT
                            candidate_membership.plan_code_snapshot,
                            candidate_membership.plan_name_snapshot,
                            candidate_membership.commercial_priority_snapshot
                        FROM member_memberships candidate_membership
                        WHERE candidate_membership.user_id = u.id
                        AND candidate_membership.status = '
                    . $activeStatus
                    . '
                        AND candidate_membership.starts_at
                            <= CURRENT_TIMESTAMP
                        AND candidate_membership.expires_at
                            > CURRENT_TIMESTAMP
                        ORDER BY
                            candidate_membership.starts_at DESC,
                            candidate_membership.id DESC
                        LIMIT 1
                    ) active_membership',
                'TRUE',
                'left',
                false
            )
            ->where(
                'u.id',
                $userId
            )
            ->get()
            ->getRowArray();

        if (!is_array($row)) {
            return [];
        }

        /*
         * Use MemberMatchScoreService itself to normalize intrinsic components.
         *
         * preference=0 is passed only so the pure scorer can normalize the
         * other components. The returned final Match Score is intentionally
         * discarded.
         */
        $normalized =
            $this->matchScoreService
            ->score(
                [
                    ...$row,

                    'match_percentage' =>
                    0,
                ]
            );

        $weights =
            $this->configurationService
            ->weights();

        return [
            'profileCompletion' =>
            max(
                0,
                min(
                    100,
                    (int) (
                        $row['profile_completion']
                        ?? 0
                    )
                )
            ),

            'approvedPhotoCount' =>
            max(
                0,
                (int) (
                    $row['approved_photo_count']
                    ?? 0
                )
            ),

            'approvedPhotoScore' =>
            (float) (
                $normalized['approvedPhotoScore']
                ?? 0
            ),

            'mobileVerified' =>
            BooleanValue::fromDatabase(
                $row['is_mobile_verified']
                    ?? false
            ),

            'emailVerified' =>
            BooleanValue::fromDatabase(
                $row['is_email_verified']
                    ?? false
            ),

            'aadhaarVerified' =>
            BooleanValue::fromDatabase(
                $row['is_aadhaar_verified']
                    ?? false
            ),

            'videoVerified' =>
            BooleanValue::fromDatabase(
                $row['is_video_introduction_verified']
                    ?? false
            ),

            'trustPoints' =>
            (int) (
                $normalized['trustPoints']
                ?? 0
            ),

            'trustScore' =>
            (float) (
                $normalized['trustScore']
                ?? 0
            ),

            'membershipPlanCode' =>
            trim(
                (string) (
                    $row['membership_plan_code']
                    ?? ''
                )
            ),

            'membershipPlanName' =>
            trim(
                (string) (
                    $row['membership_plan_name']
                    ?? ''
                )
            ),

            'commercialPriority' =>
            max(
                0,
                (int) (
                    $row['membership_commercial_priority']
                    ?? 0
                )
            ),

            'commercialScore' =>
            (float) (
                $normalized['commercialScore']
                ?? 0
            ),

            'weights' =>
            $weights,

            /*
             * Explicitly communicate why this value is absent.
             */
            'finalScore' =>
            null,

            'finalScoreReason' =>
            'Final Match Score is viewer-specific because Partner Preference compatibility depends on the viewing member.',
        ];
    }
}
