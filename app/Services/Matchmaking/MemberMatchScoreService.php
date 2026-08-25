<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Support\BooleanValue;

/**
 * Calculates the normalized Match Score for one candidate.
 *
 * IMPORTANT ARCHITECTURE RULE
 * ---------------------------
 *
 * This service performs no database queries.
 *
 * All candidate-level signals must already be projected by the discovery
 * query and Partner Preference matching must already have produced the
 * viewer-specific match percentage.
 *
 * Keeping scoring pure makes it:
 *
 * - fast;
 * - deterministic;
 * - unit-testable;
 * - safe to run over large candidate collections;
 * - independent from Search/Dashboard presentation.
 */
final class MemberMatchScoreService
{
    /*
     * Trust weighting follows MEMBERSHIP_AND_MATCHING_RULES.md:
     *
     * Mobile             1
     * Email              1
     * Aadhaar            3
     * Live Introduction  3
     *
     * Maximum            8
     */
    private const MOBILE_TRUST_POINTS =
    1;

    private const EMAIL_TRUST_POINTS =
    1;

    private const AADHAAR_TRUST_POINTS =
    3;

    private const VIDEO_TRUST_POINTS =
    3;

    private const MAX_TRUST_POINTS =
    8;

    /*
     * Approved photos are normalized rather than allowing an unlimited
     * number of uploaded photos to continuously increase ranking.
     *
     * Three approved photos are sufficient for the maximum photo component.
     * Additional photos remain useful to members but do not manipulate rank.
     */
    private const APPROVED_PHOTO_SCORE_CAP =
    3;

    /*
     * Membership commercial priorities currently map to:
     *
     * FREE = 0
     * GO   = 1
     * PLUS = 2
     * PRO  = 3
     */
    private const MAX_COMMERCIAL_PRIORITY =
    3;

    public function __construct(
        private readonly MatchScoreConfigurationService
        $configurationService
    ) {}

    /**
     * Score one candidate.
     *
     * Expected candidate inputs:
     *
     * match_percentage
     * profile_completion
     * approved_photo_count
     * is_mobile_verified
     * is_email_verified
     * is_aadhaar_verified
     * is_video_introduction_verified
     * membership_commercial_priority
     *
     * @param array<string, mixed> $candidate
     *
     * @return array{
     *     matchScore:float,
     *     preferenceScore:float,
     *     profileCompletionScore:float,
     *     approvedPhotoScore:float,
     *     trustScore:float,
     *     commercialScore:float,
     *     trustPoints:int,
     *     approvedPhotoCount:int
     * }
     */
    public function score(
        array $candidate
    ): array {
        $weights = $this
            ->configurationService
            ->weights();

        /*
         * PartnerPreferenceMatchService already returns 0..100.
         *
         * Reuse that authoritative viewer-specific calculation instead of
         * implementing preference matching again here.
         */
        $preferenceScore =
            $this->percentage(
                $candidate['match_percentage']
                    ?? 0
            );

        /*
         * Candidate projection must provide the authoritative overall
         * profile-completion percentage.
         */
        $profileCompletionScore =
            $this->percentage(
                $candidate['profile_completion']
                    ?? 0
            );

        $approvedPhotoCount = max(
            0,
            (int) (
                $candidate['approved_photo_count']
                ?? 0
            )
        );

        /*
         * Approved-photo contribution reaches 100% at the configured cap.
         *
         * 0 photos =   0
         * 1 photo  =  33.33
         * 2 photos =  66.67
         * 3+       = 100
         */
        $approvedPhotoScore =
            min(
                100.0,
                (
                    min(
                        $approvedPhotoCount,
                        self::APPROVED_PHOTO_SCORE_CAP
                    )
                    / self::APPROVED_PHOTO_SCORE_CAP
                )
                    * 100
            );

        $trustPoints =
            $this->trustPoints(
                $candidate
            );

        $trustScore =
            (
                $trustPoints
                / self::MAX_TRUST_POINTS
            )
            * 100;

        $commercialPriority =
            max(
                0,
                min(
                    self::MAX_COMMERCIAL_PRIORITY,
                    (int) (
                        $candidate['membership_commercial_priority']
                        ?? 0
                    )
                )
            );

        $commercialScore =
            (
                $commercialPriority
                / self::MAX_COMMERCIAL_PRIORITY
            )
            * 100;

        /*
         * Each normalized component is 0..100.
         *
         * Weight values total exactly 100, therefore the final Match Score
         * also remains 0..100.
         */
        $matchScore =
            (
                $preferenceScore
                * $weights['preference']
            )
            +
            (
                $profileCompletionScore
                * $weights['profileCompletion']
            )
            +
            (
                $approvedPhotoScore
                * $weights['approvedPhotos']
            )
            +
            (
                $trustScore
                * $weights['trust']
            )
            +
            (
                $commercialScore
                * $weights['commercial']
            );

        $matchScore /= 100;

        return [
            'matchScore' =>
            round(
                $matchScore,
                2
            ),

            'preferenceScore' =>
            round(
                $preferenceScore,
                2
            ),

            'profileCompletionScore' =>
            round(
                $profileCompletionScore,
                2
            ),

            'approvedPhotoScore' =>
            round(
                $approvedPhotoScore,
                2
            ),

            'trustScore' =>
            round(
                $trustScore,
                2
            ),

            'commercialScore' =>
            round(
                $commercialScore,
                2
            ),

            'trustPoints' =>
            $trustPoints,

            'approvedPhotoCount' =>
            $approvedPhotoCount,
        ];
    }

    /**
     * Attach Match Score values to an already preference-scored collection.
     *
     * This method intentionally DOES NOT sort the collection in this phase.
     *
     * Search/Dashboard ordering will be introduced separately after we have
     * verified the score inputs and query performance.
     *
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    public function scoreCandidates(
        array $candidates
    ): array {
        $result = [];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $score =
                $this->score(
                    $candidate
                );

            $candidate['match_score'] =
                $score['matchScore'];

            $candidate['match_score_components'] = [
                'preference' =>
                $score['preferenceScore'],

                'profileCompletion' =>
                $score['profileCompletionScore'],

                'approvedPhotos' =>
                $score['approvedPhotoScore'],

                'trust' =>
                $score['trustScore'],

                'commercial' =>
                $score['commercialScore'],
            ];

            $candidate['trust_points'] =
                $score['trustPoints'];

            $result[] =
                $candidate;
        }

        return $result;
    }

    /**
     * Calculate trust points from authoritative projected verification state.
     *
     * @param array<string, mixed> $candidate
     */
    private function trustPoints(
        array $candidate
    ): int {
        $points = 0;

        if (
            BooleanValue::fromDatabase(
                $candidate['is_mobile_verified']
                    ?? false
            )
        ) {
            $points +=
                self::MOBILE_TRUST_POINTS;
        }

        if (
            BooleanValue::fromDatabase(
                $candidate['is_email_verified']
                    ?? false
            )
        ) {
            $points +=
                self::EMAIL_TRUST_POINTS;
        }

        if (
            BooleanValue::fromDatabase(
                $candidate['is_aadhaar_verified']
                    ?? false
            )
        ) {
            $points +=
                self::AADHAAR_TRUST_POINTS;
        }

        if (
            BooleanValue::fromDatabase(
                $candidate['is_video_introduction_verified']
                    ?? false
            )
        ) {
            $points +=
                self::VIDEO_TRUST_POINTS;
        }

        return min(
            self::MAX_TRUST_POINTS,
            $points
        );
    }

    /**
     * Clamp an arbitrary value to the normalized 0..100 range.
     */
    private function percentage(
        mixed $value
    ): float {
        if (!is_numeric($value)) {
            return 0.0;
        }

        return max(
            0.0,
            min(
                100.0,
                (float) $value
            )
        );
    }
}
