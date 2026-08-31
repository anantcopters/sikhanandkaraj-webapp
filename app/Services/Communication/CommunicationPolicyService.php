<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\MemberCommunicationPreferenceModel;
use App\Services\Email\EmailDefinition;
use App\Services\Email\EmailRegistry;

/**
 * Central server-side communication policy.
 *
 * This service decides whether a communication may be sent through
 * a particular channel.
 *
 * Recipient-address eligibility remains a separate concern and is
 * handled by MemberEmailRecipientService.
 */
final class CommunicationPolicyService
{
    public function __construct(
        private readonly MemberCommunicationPreferenceModel
        $preferenceModel
    ) {}

    /**
     * Determine whether an email definition may be delivered
     * immediately to this member.
     */
    public function allowsImmediateEmail(
        int $userId,
        EmailDefinition $definition
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $category =
            mb_strtoupper(
                trim(
                    $definition->category
                )
            );

        /*
         * Essential communication cannot be disabled by a
         * member preference.
         */
        if (
            $this
            ->isEssentialCategory(
                $category
            )
        ) {
            return true;
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

        /*
         * No explicit preference means use the central default.
         */
        if ($preference === null) {
            return $this
                ->defaultImmediateEmailEnabled(
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
            return false;
        }

        return mb_strtoupper(
            trim(
                (string) (
                    $preference['frequency']
                    ?? ''
                )
            )
        ) ===
            MemberCommunicationPreferenceModel
            ::FREQUENCY_IMMEDIATE;
    }

    /**
     * Whether this category may be controlled by the member.
     */
    public function isMemberConfigurable(
        string $category
    ): bool {
        return !$this
            ->isEssentialCategory(
                mb_strtoupper(
                    trim(
                        $category
                    )
                )
            );
    }

    /**
     * Return the resolved email preference for Account Settings.
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
            mb_strtoupper(
                trim(
                    $category
                )
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
            $enabled =
                $this
                ->defaultImmediateEmailEnabled(
                    $category
                );

            return [
                'enabled' =>
                $enabled,

                'frequency' =>
                $enabled
                    ? MemberCommunicationPreferenceModel
                    ::FREQUENCY_IMMEDIATE
                    : MemberCommunicationPreferenceModel
                    ::FREQUENCY_OFF,

                'explicit' =>
                false,
            ];
        }

        return [
            'enabled' =>
            $this
                ->booleanValue(
                    $preference['is_enabled']
                        ?? false
                ),

            'frequency' =>
            mb_strtoupper(
                trim(
                    (string) (
                        $preference['frequency']
                        ?? MemberCommunicationPreferenceModel
                        ::FREQUENCY_OFF
                    )
                )
            ),

            'explicit' =>
            true,
        ];
    }

    /**
     * Essential application communication.
     *
     * Membership includes:
     *
     * - membership activated;
     * - membership expiring soon;
     * - membership expired.
     *
     * These must not be disabled by optional communication settings.
     */
    private function isEssentialCategory(
        string $category
    ): bool {
        return in_array(
            $category,
            [
                EmailRegistry::CATEGORY_SECURITY,
                EmailRegistry::CATEGORY_VERIFICATION,
                EmailRegistry::CATEGORY_TRANSACTIONAL,
                EmailRegistry::CATEGORY_MODERATION,
                EmailRegistry::CATEGORY_MEMBERSHIP,
                EmailRegistry::CATEGORY_SUPPORT,
            ],
            true
        );
    }

    /**
     * Current optional-email default.
     *
     * Existing Interest communication remains enabled unless the
     * member explicitly disables Matrimonial Activity emails.
     *
     * Engagement defaults OFF until the digest/engagement phase
     * actually introduces those emails.
     */
    private function defaultImmediateEmailEnabled(
        string $category
    ): bool {
        return $category ===
            EmailRegistry::CATEGORY_MATRIMONIAL_ACTIVITY;
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
