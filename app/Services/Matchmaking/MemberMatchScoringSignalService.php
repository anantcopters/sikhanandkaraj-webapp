<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\Models\MemberMatchScoringSignalModel;
use App\Services\Profile\MemberProfileSummaryService;
use RuntimeException;

/**
 * Maintains cached candidate-intrinsic Match Score signals.
 *
 * The authoritative profile-completion calculation remains inside the
 * existing ProfileCompletionService through MemberProfileSummaryService.
 *
 * This service does not implement its own completion rules.
 *
 * That is important because member Dashboard completion and Match Score
 * completion must never disagree.
 */
final class MemberMatchScoringSignalService
{
    public function __construct(
        private readonly MemberMatchScoringSignalModel
        $signalModel,

        private readonly MemberProfileSummaryService
        $profileSummaryService
    ) {}

    /**
     * Recalculate and persist intrinsic Match Score signals for one member.
     *
     * Call this only AFTER the profile mutation has successfully committed.
     *
     * A scoring-cache failure must not roll back an already valid member
     * profile update. Controllers should log refresh failures separately.
     */
    public function refreshForUser(
        int $userId
    ): void {
        if ($userId <= 0) {
            return;
        }

        /*
         * Do not resolve the profile image.
         *
         * Match Score refresh needs completion state only and must not generate
         * an unnecessary CloudFront signed URL.
         */
        $summary =
            $this->profileSummaryService
            ->getForUser(
                $userId,
                false
            );

        $completion =
            $summary['profileCompletion']
            ?? null;

        if (!is_array($completion)) {
            throw new RuntimeException(
                'Profile completion could not be calculated.'
            );
        }

        $percentage = max(
            0,
            min(
                100,
                (int) (
                    $completion['percentage']
                    ?? 0
                )
            )
        );

        $this->signalModel
            ->upsertProfileCompletion(
                $userId,
                $percentage
            );
    }
}
