<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\MemberCommunicationPreferenceModel;
use RuntimeException;

/**
 * Member-facing communication preference application service.
 */
final class MemberCommunicationPreferenceService
{
    public function __construct(
        private readonly MemberCommunicationPreferenceModel
        $preferenceModel,

        private readonly CommunicationPolicyService
        $policyService
    ) {}

    /**
     * Data consumed by Account Settings.
     *
     * Only currently configurable categories are returned.
     */
    public function settingsForMember(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new RuntimeException(
                'A valid member is required.'
            );
        }

        return [
            'matrimonial_activity' =>
            $this
                ->policyService
                ->emailPreference(
                    $userId,
                    CommunicationCategory
                    ::MATRIMONIAL_ACTIVITY
                ),

            'engagement' =>
            $this
                ->policyService
                ->emailPreference(
                    $userId,
                    CommunicationCategory
                    ::ENGAGEMENT
                ),
        ];
    }

    /**
     * Save member-controlled EMAIL preferences.
     *
     * Matrimonial Activity:
     * - enabled  => IMMEDIATE
     * - disabled => OFF
     *
     * Engagement:
     * - DAILY
     * - WEEKLY
     * - OFF
     */
    public function updateEmailPreferences(
        int $userId,
        bool $matrimonialActivity,
        string $engagementFrequency
    ): void {
        if ($userId <= 0) {
            throw new RuntimeException(
                'A valid member is required.'
            );
        }

        $this
            ->saveEmailPreference(
                $userId,
                CommunicationCategory
                ::MATRIMONIAL_ACTIVITY,
                $matrimonialActivity,
                $matrimonialActivity
                    ? MemberCommunicationPreferenceModel
                    ::FREQUENCY_IMMEDIATE
                    : MemberCommunicationPreferenceModel
                    ::FREQUENCY_OFF
            );

        $engagementFrequency =
            $this
            ->normaliseEngagementFrequency(
                $engagementFrequency
            );

        $this
            ->saveEmailPreference(
                $userId,
                CommunicationCategory
                ::ENGAGEMENT,
                $engagementFrequency !==
                    MemberCommunicationPreferenceModel
                    ::FREQUENCY_OFF,
                $engagementFrequency
            );
    }

    private function saveEmailPreference(
        int $userId,
        string $category,
        bool $enabled,
        string $frequency
    ): void {
        if (
            !$this
                ->policyService
                ->isMemberConfigurable(
                    $category
                )
        ) {
            throw new RuntimeException(
                'This communication category cannot be disabled.'
            );
        }

        if (
            !$this
                ->preferenceModel
                ->savePreference(
                    userId: $userId,

                    category: $category,

                    channel: MemberCommunicationPreferenceModel
                    ::CHANNEL_EMAIL,

                    isEnabled: $enabled,

                    frequency: $frequency
                )
        ) {
            throw new RuntimeException(
                'Communication preferences could not be saved.'
            );
        }
    }

    private function normaliseEngagementFrequency(
        string $frequency
    ): string {
        $frequency =
            mb_strtoupper(
                trim(
                    $frequency
                )
            );

        if (
            !in_array(
                $frequency,
                [
                    MemberCommunicationPreferenceModel
                    ::FREQUENCY_DAILY,

                    MemberCommunicationPreferenceModel
                    ::FREQUENCY_WEEKLY,

                    MemberCommunicationPreferenceModel
                    ::FREQUENCY_OFF,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Please select a valid Matches & Recommendations '
                    . 'email frequency.'
            );
        }

        return $frequency;
    }
}
