<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberMatchCandidateModel;
use App\Models\MemberMembershipModel;
use App\Models\MemberPhotoModel;
use App\Models\MemberVideoIntroductionModel;
use App\Models\UserModel;
use App\Services\Matchmaking\MatchScoreConfigurationService;
use App\Services\Matchmaking\MemberMatchScoreService;
use App\Services\Matchmaking\PartnerPreferenceMatchService;
use App\Support\BooleanValue;
use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Read-only administrator diagnostics for Match Score.
 *
 * Two diagnostic modes exist:
 *
 * 1. Candidate-intrinsic diagnostic
 *    --------------------------------
 *    Shows signals that belong to one candidate regardless of viewer:
 *
 *    - profile completion;
 *    - approved photos;
 *    - verification/trust;
 *    - membership commercial priority.
 *
 * 2. Directional diagnostic
 *    ----------------------
 *    Calculates the REAL final Match Score for:
 *
 *        Viewer A -> Candidate B
 *
 *    Partner Preference compatibility is directional, therefore:
 *
 *        A -> B
 *
 *    can legitimately have a different score from:
 *
 *        B -> A
 *
 * IMPORTANT:
 *
 * This service never reimplements:
 *
 * - candidate eligibility;
 * - Partner Preference matching;
 * - Match Score calculation.
 *
 * Existing production services remain authoritative.
 */
final class MemberMatchScoreDiagnosticService
{
    public function __construct(
        private readonly BaseConnection
        $database,

        private readonly UserModel
        $userModel,

        private readonly MemberMatchCandidateModel
        $candidateModel,

        private readonly PartnerPreferenceMatchService
        $partnerPreferenceMatchService,

        private readonly MatchScoreConfigurationService
        $configurationService,

        private readonly MemberMatchScoreService
        $matchScoreService
    ) {}

    /**
     * Return candidate-intrinsic ranking signals.
     *
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
         * diagnostics cannot intentionally use a different scoring definition.
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
                '(
                    SELECT
                        candidate_photo.member_id,
                        COUNT(*)::INTEGER
                            AS approved_photo_count
                    FROM member_photos candidate_photo
                    WHERE candidate_photo.status = '
                                . $approvedPhotoStatus
                                . '
                    AND candidate_photo.deleted_at IS NULL
                    GROUP BY
                        candidate_photo.member_id
                ) candidate_photos',
                            'candidate_photos.member_id = u.id',
                            'left',
                            false
            )
            ->join(
                '(
                    SELECT DISTINCT ON (
                        candidate_mobile.user_id
                    )
                        candidate_mobile.user_id,
                        candidate_mobile.is_verified
                    FROM user_contacts candidate_mobile
                    WHERE candidate_mobile.contact_type = \'MOBILE\'
                    AND candidate_mobile.is_primary = TRUE
                    ORDER BY
                        candidate_mobile.user_id,
                        candidate_mobile.id DESC
                ) primary_mobile',
                            'primary_mobile.user_id = u.id',
                            'left',
                            false
            )
            ->join(
                '(
                    SELECT DISTINCT ON (
                        candidate_email.user_id
                    )
                        candidate_email.user_id,
                        candidate_email.is_verified
                    FROM user_contacts candidate_email
                    WHERE candidate_email.contact_type = \'EMAIL\'
                    AND candidate_email.is_primary = TRUE
                    ORDER BY
                        candidate_email.user_id,
                        candidate_email.id DESC
                ) primary_email',
                            'primary_email.user_id = u.id',
                            'left',
                            false
            )

            /*
             * IMPORTANT:
             *
             * Use the actual MemberVideoIntroductionModel schema:
             *
             * member_user_id
             * moderation_status
             * is_active
             * deleted_at
             *
             * The previous diagnostic used legacy/non-existent user_id/status
             * column names.
             */
            ->join(
                '(
                    SELECT DISTINCT ON (
                        candidate_video.member_user_id
                    )
                        candidate_video.member_user_id,
                        candidate_video.id
                    FROM member_video_introductions candidate_video
                    WHERE candidate_video.moderation_status = '
                                . $approvedVideoStatus
                                . '
                    AND candidate_video.is_active = TRUE
                    AND candidate_video.deleted_at IS NULL
                    ORDER BY
                        candidate_video.member_user_id,
                        candidate_video.updated_at DESC,
                        candidate_video.id DESC
                ) approved_video',
                            'approved_video.member_user_id = u.id',
                            'left',
                            false
            )
            ->join(
                '(
                    SELECT DISTINCT ON (
                        candidate_membership.user_id
                    )
                        candidate_membership.user_id,
                        candidate_membership.plan_code_snapshot,
                        candidate_membership.plan_name_snapshot,
                        candidate_membership.commercial_priority_snapshot
                    FROM member_memberships candidate_membership
                    WHERE candidate_membership.status = '
                                . $activeStatus
                                . '
                    AND candidate_membership.starts_at
                        <= CURRENT_TIMESTAMP
                    AND candidate_membership.expires_at
                        > CURRENT_TIMESTAMP
                    ORDER BY
                        candidate_membership.user_id,
                        candidate_membership.starts_at DESC,
                        candidate_membership.id DESC
                ) active_membership',
                            'active_membership.user_id = u.id',
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
         * Use the real scorer to normalize intrinsic components.
         *
         * match_percentage=0 is ONLY used to obtain normalization for the
         * intrinsic components.
         *
         * The resulting final Match Score is deliberately discarded because
         * there is no viewer in this diagnostic mode.
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

            'finalScore' =>
            null,

            'finalScoreReason' =>
            'Final Match Score is viewer-specific because Partner Preference compatibility depends on the viewing member.',
        ];
    }

    /**
     * Compare the Admin profile member against another member.
     *
     * Both directions are calculated independently:
     *
     *     profile member -> comparison member
     *
     * and:
     *
     *     comparison member -> profile member
     *
     * @return array<string, mixed>
     */
    public function compare(
        int $profileMemberId,
        string $comparisonProfileReference
    ): array {
        if ($profileMemberId <= 0) {
            throw new DomainException(
                'The member profile could not be found.'
            );
        }

        $comparisonProfileReference =
            mb_strtoupper(
                trim(
                    $comparisonProfileReference
                )
            );

        if ($comparisonProfileReference === '') {
            throw new DomainException(
                'Please enter a Profile ID.'
            );
        }

        /*
         * Resolve both actual member accounts first.
         *
         * This lets us distinguish:
         *
         * - invalid Profile ID;
         * - same member;
         * - member exists but is not an eligible candidate.
         */
        $profileMember =
            $this->userModel
            ->find(
                $profileMemberId
            );

        if (!is_array($profileMember)) {
            throw new DomainException(
                'The member profile could not be found.'
            );
        }

        $comparisonMember =
            $this->userModel
            ->where(
                'profile_ref_number',
                $comparisonProfileReference
            )
            ->first();

        if (!is_array($comparisonMember)) {
            throw new DomainException(
                'No member was found for the entered Profile ID.'
            );
        }

        $comparisonMemberId =
            max(
                0,
                (int) (
                    $comparisonMember['id']
                    ?? 0
                )
            );

        if ($comparisonMemberId <= 0) {
            throw new DomainException(
                'No member was found for the entered Profile ID.'
            );
        }

        if ($comparisonMemberId === $profileMemberId) {
            throw new DomainException(
                'Please enter a different member Profile ID.'
            );
        }

        /*
         * Direction 1:
         *
         * Current Admin profile member is the viewer.
         */
        $forward =
            $this->directionalScore(
                $profileMember,
                $comparisonMember
            );

        /*
         * Direction 2:
         *
         * Comparison member becomes the viewer.
         *
         * This must be independently calculated because Partner Preferences
         * are directional.
         */
        $reverse =
            $this->directionalScore(
                $comparisonMember,
                $profileMember
            );

        return [
            'profileMember' =>
            $this->memberIdentity(
                $profileMember
            ),

            'comparisonMember' =>
            $this->memberIdentity(
                $comparisonMember
            ),

            'forward' =>
            $forward,

            'reverse' =>
            $reverse,
        ];
    }

    /**
     * Calculate one viewer -> candidate diagnostic.
     *
     * @param array<string, mixed> $viewer
     * @param array<string, mixed> $candidateMember
     *
     * @return array<string, mixed>
     */
    private function directionalScore(
        array $viewer,
        array $candidateMember
    ): array {
        $viewerId =
            max(
                0,
                (int) (
                    $viewer['id']
                    ?? 0
                )
            );

        $candidateId =
            max(
                0,
                (int) (
                    $candidateMember['id']
                    ?? 0
                )
            );

        $viewerGender =
            trim(
                (string) (
                    $viewer['gender']
                    ?? ''
                )
            );

        /*
         * CRITICAL:
         *
         * Do not directly query scoring tables here.
         *
         * MemberMatchCandidateModel remains the authority for whether this
         * member is a currently visible/eligible candidate and also supplies
         * exactly the same candidate scoring projection used by production
         * Search/Dashboard.
         */
        $candidateRows =
            $this->candidateModel
            ->visibleCandidatesByIds(
                $viewerId,
                $viewerGender,
                [
                    $candidateId,
                ]
            );

        if ($candidateRows === []) {
            return [
                'eligible' =>
                false,

                'reason' =>
                'This member is not currently an eligible candidate for the viewing member.',

                'score' =>
                null,
            ];
        }

        /*
         * There can be only one requested candidate.
         */
        $candidate =
            $candidateRows[0];

        /*
         * Use the production Partner Preference algorithm.
         *
         * Never recreate preference comparison inside Admin diagnostics.
         */
        $preferenceScored =
            $this->partnerPreferenceMatchService
            ->scoreCandidates(
                $viewerId,
                [
                    $candidate,
                ]
            );

        if (
            $preferenceScored === []
            || !is_array(
                $preferenceScored[0]
                    ?? null
            )
        ) {
            return [
                'eligible' =>
                false,

                'reason' =>
                'Partner Preference compatibility could not be calculated.',

                'score' =>
                null,
            ];
        }

        $scoredCandidate =
            $preferenceScored[0];

        /*
         * Use the same final scorer used by Dashboard and Search.
         */
        $score =
            $this->matchScoreService
            ->score(
                $scoredCandidate
            );

        return [
            'eligible' =>
            true,

            'reason' =>
            '',

            'passesCompulsory' => ($scoredCandidate['passes_compulsory'] ?? true)
                === true,

            'matchPercentage' =>
            (float) (
                $scoredCandidate['match_percentage']
                ?? 0
            ),

            'matchScore' =>
            (float) (
                $score['matchScore']
                ?? 0
            ),

            'preferenceScore' =>
            (float) (
                $score['preferenceScore']
                ?? 0
            ),

            'profileCompletionScore' =>
            (float) (
                $score['profileCompletionScore']
                ?? 0
            ),

            'approvedPhotoScore' =>
            (float) (
                $score['approvedPhotoScore']
                ?? 0
            ),

            'trustScore' =>
            (float) (
                $score['trustScore']
                ?? 0
            ),

            'commercialScore' =>
            (float) (
                $score['commercialScore']
                ?? 0
            ),

            'trustPoints' =>
            (int) (
                $score['trustPoints']
                ?? 0
            ),

            'approvedPhotoCount' =>
            (int) (
                $score['approvedPhotoCount']
                ?? 0
            ),

            'weights' =>
            is_array(
                $score['weights']
                    ?? null
            )
                ? $score['weights']
                : [],

            'weightedContributions' =>
            is_array(
                $score['weightedContributions']
                    ?? null
            )
                ? $score['weightedContributions']
                : [],
        ];
    }

    /**
     * Return only the identity information required by Admin diagnostics.
     *
     * @param array<string, mixed> $member
     *
     * @return array<string, mixed>
     */
    private function memberIdentity(
        array $member
    ): array {
        return [
            'id' =>
            max(
                0,
                (int) (
                    $member['id']
                    ?? 0
                )
            ),

            'name' =>
            trim(
                (string) (
                    $member['full_name']
                    ?? ''
                )
            ),

            'profileReference' =>
            trim(
                (string) (
                    $member['profile_ref_number']
                    ?? ''
                )
            ),

            'gender' =>
            trim(
                (string) (
                    $member['gender']
                    ?? ''
                )
            ),
        ];
    }
}
