<?php

declare(strict_types=1);

namespace App\Services\Profile;

/**
 * Calculates overall member profile completion.
 */
final class ProfileCompletionService
{
    /**
     * Calculate completion across all implemented profile sections.
     *
     * @param array<string, int> $basicDetailsCompletion
     * @param array<string, int> $educationProfessionCompletion
     * @param array<string, int> $familyDetailsCompletion
     * @param array<string, int> $sikhReligiousDetailsCompletion
     * @param array<string, int> $lifestyleCompletion
     * @param array<string, int> $aboutMeCompletion
     *
     * @return array{
     *     percentage: int,
     *     completedSteps: int,
     *     totalSteps: int
     * }
     */
    public function calculate(
        array $basicDetailsCompletion,
        array $educationProfessionCompletion,
        array $familyDetailsCompletion,
        array $sikhReligiousDetailsCompletion,
        array $lifestyleCompletion,
        array $aboutMeCompletion
    ): array {
        $sections = [
            $this->isSectionComplete(
                $basicDetailsCompletion
            ),
            $this->isSectionComplete(
                $educationProfessionCompletion
            ),
            $this->isSectionComplete(
                $familyDetailsCompletion
            ),
            $this->isSectionComplete(
                $sikhReligiousDetailsCompletion
            ),
            $this->isSectionComplete(
                $lifestyleCompletion
            ),
            $this->isSectionComplete(
                $aboutMeCompletion
            ),
        ];

        $totalSteps = count($sections);

        $completedSteps = count(
            array_filter(
                $sections,
                static fn(bool $complete): bool =>
                $complete
            )
        );

        return [
            'percentage' => $totalSteps > 0
                ? (int) round(
                    ($completedSteps / $totalSteps) * 100
                )
                : 0,

            'completedSteps' => $completedSteps,

            'totalSteps' => $totalSteps,
        ];
    }

    /**
     * Determine whether a profile section is fully complete.
     *
     * @param array<string, int> $completion
     */
    private function isSectionComplete(
        array $completion
    ): bool {
        return (
            (int) ($completion['percentage'] ?? 0)
        ) === 100;
    }
}
