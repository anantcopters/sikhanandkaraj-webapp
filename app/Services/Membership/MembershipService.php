<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipModel;
use App\Models\MembershipPlanModel;
use App\Support\BooleanValue;

/**
 * Authoritative member membership resolver.
 *
 * FREE is intentionally a derived state rather than a persisted plan.
 *
 * If no currently usable paid membership exists, the member is Free.
 * No caller should inspect membership tables or legacy account flags to
 * independently decide whether a member is paid.
 */
final class MembershipService
{
    public const ACCOUNT_FREE = 'FREE';

    public const ACCOUNT_GO = MembershipPlanModel::CODE_GO;

    public const ACCOUNT_PLUS = MembershipPlanModel::CODE_PLUS;

    public const ACCOUNT_PRO = MembershipPlanModel::CODE_PRO;

    public function __construct(
        private readonly MembershipPlanModel $planModel,
        private readonly MemberMembershipModel $membershipModel
    ) {}

    /**
     * Return all active commercial plans.
     *
     * This is the data source that the pricing presentation should consume
     * when pricing is wired to the new membership foundation.
     *
     * @return list<array<string, mixed>>
     */
    public function activePlans(): array
    {
        return array_map(
            fn(array $plan): array =>
            $this->normalizePlan(
                $plan
            ),
            $this->planModel->activePlans()
        );
    }

    /**
     * Resolve the member's current account state.
     *
     * @return array{
     *     accountType:string,
     *     accountLabel:string,
     *     isPaid:bool,
     *     membership:?array<string, mixed>
     * }
     */
    public function resolveForUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            return $this->freeMembership();
        }

        $membership = $this
            ->membershipModel
            ->activeForUser(
                $userId,
                $this->nowUtc()
            );

        if (!is_array($membership)) {
            return $this->freeMembership();
        }

        $planCode = mb_strtoupper(
            trim(
                (string) (
                    $membership['plan_code_snapshot']
                    ?? ''
                )
            )
        );

        if (
            !in_array(
                $planCode,
                [
                    self::ACCOUNT_GO,
                    self::ACCOUNT_PLUS,
                    self::ACCOUNT_PRO,
                ],
                true
            )
        ) {
            /*
             * Fail closed.
             *
             * A corrupt/unknown membership must never accidentally grant
             * paid capabilities.
             */
            return $this->freeMembership();
        }

        return [
            'accountType' =>
            $planCode,

            'accountLabel' =>
            $this->accountLabel(
                $planCode
            ),

            'isPaid' =>
            true,

            'membership' => [
                'id' =>
                (int) (
                    $membership['id']
                    ?? 0
                ),

                'planCode' =>
                $planCode,

                'planName' =>
                trim(
                    (string) (
                        $membership['plan_name_snapshot']
                        ?? ''
                    )
                ),

                'startsAt' =>
                (string) (
                    $membership['starts_at']
                    ?? ''
                ),

                'expiresAt' =>
                (string) (
                    $membership['expires_at']
                    ?? ''
                ),

                'profileViewLimit' =>
                (int) (
                    $membership['profile_view_limit_snapshot']
                    ?? 0
                ),

                'dailyProfileViewLimit' =>
                (int) (
                    $membership['daily_profile_view_limit_snapshot']
                    ?? 0
                ),

                'liveIntroductionViewLimit' =>
                (int) (
                    $membership['live_introduction_view_limit_snapshot']
                    ?? 0
                ),

                'hasMatchManager' =>
                BooleanValue::fromDatabase(
                    $membership['has_match_manager_snapshot']
                        ?? false
                ),

                'commercialPriority' =>
                (int) (
                    $membership['commercial_priority_snapshot']
                    ?? 0
                ),
            ],
        ];
    }

    /**
     * Return true when the member currently has any paid membership.
     *
     * Callers that need a specific product capability should use
     * MembershipEntitlementService rather than this convenience method.
     */
    public function hasPaidMembership(
        int $userId
    ): bool {
        return $this
            ->resolveForUser(
                $userId
            )['isPaid'] === true;
    }

    /**
     * Normalize one plan-master row for presentation/application use.
     *
     * @param array<string, mixed> $plan
     *
     * @return array<string, mixed>
     */
    private function normalizePlan(
        array $plan
    ): array {
        return [
            'id' =>
            (int) (
                $plan['id']
                ?? 0
            ),

            'code' =>
            mb_strtoupper(
                trim(
                    (string) (
                        $plan['code']
                        ?? ''
                    )
                )
            ),

            'name' =>
            trim(
                (string) (
                    $plan['name']
                    ?? ''
                )
            ),

            'positioning' =>
            trim(
                (string) (
                    $plan['positioning']
                    ?? ''
                )
            ),

            'pricePaise' =>
            (int) (
                $plan['price_paise']
                ?? 0
            ),

            'durationMonths' =>
            (int) (
                $plan['duration_months']
                ?? 0
            ),

            'profileViewLimit' =>
            (int) (
                $plan['profile_view_limit']
                ?? 0
            ),

            'dailyProfileViewLimit' =>
            (int) (
                $plan['daily_profile_view_limit']
                ?? 0
            ),

            'liveIntroductionViewLimit' =>
            (int) (
                $plan['live_introduction_view_limit']
                ?? 0
            ),

            'hasMatchManager' =>
            BooleanValue::fromDatabase(
                $plan['has_match_manager']
                    ?? false
            ),

            'commercialPriority' =>
            (int) (
                $plan['commercial_priority']
                ?? 0
            ),

            'displayOrder' =>
            (int) (
                $plan['display_order']
                ?? 0
            ),
        ];
    }

    /**
     * Build the derived Free-account state.
     *
     * Free is not stored in membership_plans because it is the absence of an
     * active commercial membership, not a purchased membership instance.
     *
     * @return array{
     *     accountType:string,
     *     accountLabel:string,
     *     isPaid:bool,
     *     membership:null
     * }
     */
    private function freeMembership(): array
    {
        return [
            'accountType' =>
            self::ACCOUNT_FREE,

            'accountLabel' =>
            'Free Account',

            'isPaid' =>
            false,

            'membership' =>
            null,
        ];
    }

    /**
     * Return the customer-facing account label.
     */
    private function accountLabel(
        string $accountType
    ): string {
        return match ($accountType) {
            self::ACCOUNT_GO =>
            'Sikhanandkaraj Go',

            self::ACCOUNT_PLUS =>
            'Sikhanandkaraj Plus',

            self::ACCOUNT_PRO =>
            'Sikhanandkaraj Pro',

            default =>
            'Free Account',
        };
    }

    /**
     * Application/database timestamps are stored in UTC.
     */
    private function nowUtc(): string
    {
        return (
            new \DateTimeImmutable(
                'now',
                new \DateTimeZone('UTC')
            )
        )->format(
            'Y-m-d H:i:s'
        );
    }
}
