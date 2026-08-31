<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\MemberCommunicationPreferenceModel;
use App\Services\Email\EmailRegistry;

/**
 * Central server-side communication policy.
 *
 * This service answers:
 *
 * "May this member receive this communication through this channel?"
 *
 * It deliberately separates:
 *
 * - essential communication;
 * - optional member-controlled communication;
 * - future digest behaviour.
 *
 * No controller/template should independently decide these rules.
 */
final class CommunicationPolicyService
{
    public function __construct(
        private readonly MemberCommunicationPreferenceModel
        $preferenceModel
    ) {}

    /**
     * Determine whether an EMAIL event may be sent immediately.
     *
     * NOTE:
     *
     * Verified-email eligibility remains the responsibility of
     * MemberEmailRecipientService.
     *
     * This service only answers communication-policy questions.
     */
    public function allowsImmediateEmail(
        int $userId,
        string $eventIdentifier
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $category =
            $this
            ->categoryForEvent(
                $eventIdentifier
            );

        /*
         * Essential categories are not member-disableable.
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
         * No explicit row means use the approved default.
         *
         * MATRIMONIAL_ACTIVITY currently defaults to immediate email
         * because the existing Interest email implementation is already
         * active.
         *
         * ENGAGEMENT does not default to immediate email because those
         * events are intended for digest/preferences.
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
     * Whether the member is allowed to control this category.
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
     * Current member-facing email preference.
     *
     * This gives the Account Settings UI a resolved value even when
     * no database override exists.
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
            return [
                'enabled' =>
                $this
                    ->defaultImmediateEmailEnabled(
                        $category
                    ),

                'frequency' =>
                $this
                    ->defaultImmediateEmailEnabled(
                        $category
                    )
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
     * Essential communication cannot be disabled by member preference.
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
     * Approved default behaviour where no member override exists.
     */
    private function defaultImmediateEmailEnabled(
        string $category
    ): bool {
        return $category ===
            EmailRegistry::CATEGORY_MATRIMONIAL_ACTIVITY;
    }

    /**
     * Resolve the category from the same central EmailRegistry used by
     * production email definitions.
     *
     * No duplicate event/category map should be created here.
     */
    private function categoryForEvent(
        string $eventIdentifier
    ): string {
        $definition =
            EmailRegistry::definition(
                $eventIdentifier
            );

        return mb_strtoupper(
            trim(
                $definition->category
            )
        );
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
