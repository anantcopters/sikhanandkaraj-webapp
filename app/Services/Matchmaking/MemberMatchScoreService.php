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
 * - independent from Search/Dashboard/Admin presentation.
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
    private const MOBILE_TRUST_POINTS = 1;

    private const EMAIL_TRUST_POINTS = 1;

    private const AADHAAR_TRUST_POINTS = 3;

    private const VIDEO_TRUST_POINTS = 3;

    private const MAX_TRUST_POINTS = 8;

    /*
     * Approved photos are normalized rather than allowing an unlimited
     * number of uploaded photos to continuously increase ranking.
     *
     * Three approved photos are sufficient for the maximum photo component.
     */
    private const APPROVED_PHOTO_SCORE_CAP = 3;

    /*
     * Membership commercial priorities currently map to:
     *
     * FREE = 0
     * GO   = 1
     * PLUS = 2
     * PRO  = 3
     */
    private const MAX_COMMERCIAL_PRIORITY = 3;

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
     * @return array<string, mixed>
     */
    public function score(
        array $candidate
    ): array {
        $weights =
            $this->configurationService
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
         * Candidate projection provides the authoritative cached
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
         * Approved-photo contribution reaches 100% at three approved photos.
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
         * Calculate the ACTUAL contribution of every component.
         *
         * This belongs in the scoring authority rather than Admin diagnostics.
         *
         * Example:
         *
         * preference score = 80
         * preference weight = 50
         *
         * contribution = 40 Match Score points.
         */
        $weightedContributions = [
            'preference' =>
            $this->weightedContribution(
                $preferenceScore,
                (float) $weights['preference']
            ),

            'profileCompletion' =>
            $this->weightedContribution(
                $profileCompletionScore,
                (float) $weights['profileCompletion']
            ),

            'approvedPhotos' =>
            $this->weightedContribution(
                $approvedPhotoScore,
                (float) $weights['approvedPhotos']
            ),

            'trust' =>
            $this->weightedContribution(
                $trustScore,
                (float) $weights['trust']
            ),

            'commercial' =>
            $this->weightedContribution(
                $commercialScore,
                (float) $weights['commercial']
            ),
        ];

        /*
         * The final Match Score is simply the sum of authoritative weighted
         * contributions.
         *
         * This also makes Admin diagnostics explainable without recreating
         * scoring mathematics elsewhere.
         */
        $matchScore =
            array_sum(
                $weightedContributions
            );

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

            /*
             * Expose the exact active configuration used for this score.
             *
             * Diagnostics therefore describe the score that was actually
             * calculated rather than independently resolving configuration.
             */
            'weights' =>
            $weights,

            /*
             * Actual Match Score points contributed by each component.
             */
            'weightedContributions' =>
            $weightedContributions,
        ];
    }

    /**
     * Attach Match Score values to an already preference-scored collection.
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

            /*
             * Keep weighted contributions available to internal consumers.
             *
             * Member presentation does not need to expose these values.
             */
            $candidate['match_score_contributions'] =
                $score['weightedContributions'];

            $candidate['trust_points'] =
                $score['trustPoints'];

            $result[] =
                $candidate;
        }

        return $result;
    }

    /**
     * Apply the canonical deterministic Match Score ranking.
     *
     * Ranking order:
     *
     * 1. Final Match Score
     * 2. Partner Preference score
     * 3. Trust score
     * 4. Profile completion
     * 5. Approved photo count
     * 6. Newest member
     * 7. Highest user ID
     *
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    public function rankCandidates(
        array $candidates
    ): array {
        $scored =
            $this->scoreCandidates(
                $candidates
            );

        usort(
            $scored,

            static function (
                array $left,
                array $right
            ): int {
                $comparison =
                    ((float) (
                        $right['match_score']
                        ?? 0
                    ))
                    <=>
                    ((float) (
                        $left['match_score']
                        ?? 0
                    ));

                if ($comparison !== 0) {
                    return $comparison;
                }

                /*
                 * Preference remains the strongest tie-breaker because
                 * matrimonial relevance must outrank commercial/profile
                 * presentation signals.
                 */
                $comparison =
                    ((float) (
                        $right['match_percentage']
                        ?? 0
                    ))
                    <=>
                    ((float) (
                        $left['match_percentage']
                        ?? 0
                    ));

                if ($comparison !== 0) {
                    return $comparison;
                }

                $comparison =
                    ((float) (
                        $right['match_score_components']['trust']
                        ?? 0
                    ))
                    <=>
                    ((float) (
                        $left['match_score_components']['trust']
                        ?? 0
                    ));

                if ($comparison !== 0) {
                    return $comparison;
                }

                $comparison =
                    ((int) (
                        $right['profile_completion']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $left['profile_completion']
                        ?? 0
                    ));

                if ($comparison !== 0) {
                    return $comparison;
                }

                $comparison =
                    ((int) (
                        $right['approved_photo_count']
                        ?? 0
                    ))
                    <=>
                    ((int) (
                        $left['approved_photo_count']
                        ?? 0
                    ));

                if ($comparison !== 0) {
                    return $comparison;
                }

                /*
                 * PostgreSQL/ISO timestamps remain lexicographically sortable
                 * under the current candidate projection.
                 */
                $comparison =
                    strcmp(
                        (string) (
                            $right['created_at']
                            ?? ''
                        ),
                        (string) (
                            $left['created_at']
                            ?? ''
                        )
                    );

                if ($comparison !== 0) {
                    return $comparison;
                }

                /*
                 * Final deterministic tie-breaker.
                 */
                return ((int) (
                    $right['id']
                    ?? 0
                ))
                    <=>
                    ((int) (
                        $left['id']
                        ?? 0
                    ));
            }
        );

        return array_values(
            $scored
        );
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
     * Calculate the number of final Match Score points contributed by one
     * normalized component.
     */
    private function weightedContribution(
        float $componentScore,
        float $weight
    ): float {
        return round(
            (
                $componentScore
                * $weight
            )
                / 100,
            2
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
