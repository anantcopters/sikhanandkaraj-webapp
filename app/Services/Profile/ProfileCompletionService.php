<?php

declare(strict_types=1);

namespace App\Services\Profile;

/**
 * Calculates overall member profile completion from implemented sections.
 *
 * Add each new profile section here when that section becomes functional.
 */
final class ProfileCompletionService
{
    /**
     * Build overall completion from individual section completion values.
     *
     * @param array<string, int> $basicDetailsCompletion
     * @param array<string, int> $educationProfessionCompletion
     *
     * @return array{
     *     percentage: int,
     *     completedSteps: int,
     *     totalSteps: int
     * }
     */
    public function calculate(
        array $basicDetailsCompletion,
        array $educationProfessionCompletion
    ): array {
        $sections = [
            $this->isSectionComplete(
                $basicDetailsCompletion
            ),
            $this->isSectionComplete(
                $educationProfessionCompletion
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

        $percentage = $totalSteps > 0
            ? (int) round(
                ($completedSteps / $totalSteps) * 100
            )
            : 0;

        return [
            'percentage' => $percentage,
            'completedSteps' => $completedSteps,
            'totalSteps' => $totalSteps,
        ];
    }

    /**
     * Determine whether one profile section is complete.
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
