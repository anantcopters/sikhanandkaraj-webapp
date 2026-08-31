<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\MemberCommunicationPreferenceModel;
use App\Services\Email\EmailRegistry;
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
                    EmailRegistry
                    ::CATEGORY_MATRIMONIAL_ACTIVITY
                ),

            'engagement' =>
            $this
                ->policyService
                ->emailPreference(
                    $userId,
                    EmailRegistry
                    ::CATEGORY_ENGAGEMENT
                ),
        ];
    }

    /**
     * Save member-controlled EMAIL preferences.
     *
     * Phase 3A deliberately exposes only the settings that are already
     * meaningful to the current email architecture.
     */
    public function updateEmailPreferences(
        int $userId,
        bool $matrimonialActivity,
        bool $engagement
    ): void {
        if ($userId <= 0) {
            throw new RuntimeException(
                'A valid member is required.'
            );
        }

        $this
            ->saveEmailToggle(
                $userId,
                EmailRegistry
                ::CATEGORY_MATRIMONIAL_ACTIVITY,
                $matrimonialActivity
            );

        $this
            ->saveEmailToggle(
                $userId,
                EmailRegistry
                ::CATEGORY_ENGAGEMENT,
                $engagement
            );
    }

    private function saveEmailToggle(
        int $userId,
        string $category,
        bool $enabled
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

                    frequency: $enabled
                        ? MemberCommunicationPreferenceModel
                        ::FREQUENCY_IMMEDIATE
                        : MemberCommunicationPreferenceModel
                        ::FREQUENCY_OFF
                )
        ) {
            throw new RuntimeException(
                'Communication preferences could not be saved.'
            );
        }
    }
}
