<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\MemberCommunicationPreferenceModel;
use App\Services\Email\EmailDefinition;

/**
 * Central server-side communication policy.
 *
 * The policy determines HOW a communication may be delivered.
 *
 * Recipient-address eligibility remains a separate concern and is
 * handled by the appropriate channel recipient service.
 */
final class CommunicationPolicyService
{
    public function __construct(
        private readonly MemberCommunicationPreferenceModel
        $preferenceModel
    ) {}

    /**
     * Backward-compatible decision used by the existing immediate
     * member-email pipeline.
     */
    public function allowsImmediateEmail(
        int $userId,
        EmailDefinition $definition
    ): bool {
        return $this
            ->emailDeliveryDecision(
                $userId,
                $definition->category
            ) ===
            CommunicationDeliveryDecision
            ::IMMEDIATE;
    }

    /**
     * Resolve the member's EMAIL delivery decision for a communication
     * category.
     *
     * Essential categories always remain IMMEDIATE.
     *
     * Optional categories respect the persisted member preference.
     */
    public function emailDeliveryDecision(
        int $userId,
        string $category
    ): string {
        if ($userId <= 0) {
            return CommunicationDeliveryDecision
            ::SKIP;
        }

        $category =
            $this
            ->normaliseCategory(
                $category
            );

        /*
         * Essential communication is never disabled or delayed by
         * optional member communication preferences.
         */
        if (
            $this
            ->isEssentialCategory(
                $category
            )
        ) {
            return CommunicationDeliveryDecision
            ::IMMEDIATE;
        }

        $preference =
            $this
            ->preferenceModel
            ->findPreference(
                $userId,
                $category,
                MemberCommunicationPreferenceModel
                ::CHANNEL_EMAIL
            );

        if ($preference === null) {
            return $this
                ->defaultEmailDeliveryDecision(
                    $category
                );
        }

        if (
            !$this
                ->booleanValue(
                    $preference['is_enabled']
                        ?? false
                )
        ) {
            return CommunicationDeliveryDecision
            ::SKIP;
        }

        return $this
            ->frequencyToDecision(
                (string) (
                    $preference['frequency']
                    ?? ''
                )
            );
    }

    /**
     * Whether this category may be controlled by the member.
     */
    public function isMemberConfigurable(
        string $category
    ): bool {
        return !$this
            ->isEssentialCategory(
                $this
                    ->normaliseCategory(
                        $category
                    )
            );
    }

    /**
     * Return the resolved email preference consumed by Account Settings.
     *
     * @return array{
     *     enabled:bool,
     *     frequency:string,
     *     explicit:bool
     * }
     */
    public function emailPreference(
        int $userId,
        string $category
    ): array {
        $category =
            $this
            ->normaliseCategory(
                $category
            );

        $preference =
            $this
            ->preferenceModel
            ->findPreference(
                $userId,
                $category,
                MemberCommunicationPreferenceModel
                ::CHANNEL_EMAIL
            );

        if ($preference === null) {
            $decision =
                $this
                ->defaultEmailDeliveryDecision(
                    $category
                );

            return [
                'enabled' =>
                $decision !==
                    CommunicationDeliveryDecision
                    ::SKIP,

                'frequency' =>
                $this
                    ->decisionToFrequency(
                        $decision
                    ),

                'explicit' =>
                false,
            ];
        }

        $enabled =
            $this
            ->booleanValue(
                $preference['is_enabled']
                    ?? false
            );

        return [
            'enabled' =>
            $enabled,

            'frequency' =>
            $enabled
                ? $this
                ->normaliseFrequency(
                    (string) (
                        $preference['frequency']
                        ?? ''
                    )
                )
                : MemberCommunicationPreferenceModel
                ::FREQUENCY_OFF,

            'explicit' =>
            true,
        ];
    }

    /**
     * Essential communication categories.
     */
    private function isEssentialCategory(
        string $category
    ): bool {
        return in_array(
            $category,
            [
                CommunicationCategory::SECURITY,
                CommunicationCategory::VERIFICATION,
                CommunicationCategory::TRANSACTIONAL,
                CommunicationCategory::MODERATION,
                CommunicationCategory::MEMBERSHIP,
                CommunicationCategory::SUPPORT,
            ],
            true
        );
    }

    /**
     * Defaults when the member has not explicitly saved a preference.
     *
     * Existing Interest email behaviour remains unchanged.
     *
     * Engagement defaults to DAILY so high-frequency events such as
     * Profile Viewed and Shortlisted do not become immediate emails.
     */
    private function defaultEmailDeliveryDecision(
        string $category
    ): string {
        if (
            $category ===
            CommunicationCategory
            ::MATRIMONIAL_ACTIVITY
        ) {
            return CommunicationDeliveryDecision
            ::IMMEDIATE;
        }

        if (
            $category ===
            CommunicationCategory
            ::ENGAGEMENT
        ) {
            return CommunicationDeliveryDecision
            ::DAILY;
        }

        return CommunicationDeliveryDecision
        ::SKIP;
    }

    private function frequencyToDecision(
        string $frequency
    ): string {
        return match ($this
            ->normaliseFrequency(
                $frequency
            )) {
            MemberCommunicationPreferenceModel
            ::FREQUENCY_IMMEDIATE =>
            CommunicationDeliveryDecision
            ::IMMEDIATE,

            MemberCommunicationPreferenceModel
            ::FREQUENCY_DAILY =>
            CommunicationDeliveryDecision
            ::DAILY,

            MemberCommunicationPreferenceModel
            ::FREQUENCY_WEEKLY =>
            CommunicationDeliveryDecision
            ::WEEKLY,

            default =>
            CommunicationDeliveryDecision
            ::SKIP,
        };
    }

    private function decisionToFrequency(
        string $decision
    ): string {
        return match ($decision) {
            CommunicationDeliveryDecision
            ::IMMEDIATE =>
            MemberCommunicationPreferenceModel
            ::FREQUENCY_IMMEDIATE,

            CommunicationDeliveryDecision
            ::DAILY =>
            MemberCommunicationPreferenceModel
            ::FREQUENCY_DAILY,

            CommunicationDeliveryDecision
            ::WEEKLY =>
            MemberCommunicationPreferenceModel
            ::FREQUENCY_WEEKLY,

            default =>
            MemberCommunicationPreferenceModel
            ::FREQUENCY_OFF,
        };
    }

    private function normaliseCategory(
        string $category
    ): string {
        return mb_strtoupper(
            trim(
                $category
            )
        );
    }

    private function normaliseFrequency(
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
                    ::FREQUENCY_IMMEDIATE,

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
            return MemberCommunicationPreferenceModel
            ::FREQUENCY_OFF;
        }

        return $frequency;
    }

    private function booleanValue(
        mixed $value
    ): bool {
        return $value === true
            || $value === 1
            || $value === '1'
            || $value === 't'
            || $value === 'true';
    }
}
