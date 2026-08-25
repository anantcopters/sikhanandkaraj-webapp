<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MembershipPlanModel;

/**
 * Builds the authoritative membership-plan presentation used by:
 *
 * - public pricing;
 * - Account Settings -> Membership Plans.
 *
 * COMMERCIAL SOURCE OF TRUTH
 * ==========================
 *
 * Price, duration, profile limits, daily limits and Live Introduction
 * allowances come only from membership_plans through MembershipService.
 *
 * Views must never maintain another commercial copy.
 *
 * Presentation-only information such as image filenames and "popular"
 * highlighting may remain application configuration because those values
 * do not grant capabilities or define commercial entitlement.
 */
final class MembershipPlanPresentationService
{
    /**
     * Presentation metadata keyed by stable commercial plan code.
     *
     * IMPORTANT:
     *
     * Nothing in this array may define price, duration, entitlement or quota.
     */
    private const PRESENTATION = [
        MembershipPlanModel::CODE_GO => [
            'image' =>
            'plan_go_short_removebg.png',

            'popular' =>
            false,

            'description' =>
            'A simple way to begin your search and connect '
                . 'with verified profiles.',
        ],

        MembershipPlanModel::CODE_PLUS => [
            'image' =>
            'plan_plus_short_removebg.png',

            'popular' =>
            true,

            'description' =>
            'More time, more verified profiles and more '
                . 'opportunities to connect.',
        ],

        MembershipPlanModel::CODE_PRO => [
            'image' =>
            'plan_pro_short_removebg.png',

            'popular' =>
            false,

            'description' =>
            'For members and families who want personalised '
                . 'assistance throughout their matrimonial search.',
        ],
    ];

    public function __construct(
        private readonly MembershipService
        $membershipService,

        private readonly MembershipPurchaseService
        $purchaseService
    ) {}

    /**
     * Build public pricing data.
     *
     * Public visitors have no purchase-transition state because there is no
     * authenticated membership to compare against.
     *
     * @return list<array<string, mixed>>
     */
    public function publicPlans(): array
    {
        return array_map(
            fn(array $plan): array =>
            $this->presentPlan(
                $plan,
                null
            ),
            $this
                ->membershipService
                ->activePlans()
        );
    }

    /**
     * Build Account Settings plan data for one authenticated member.
     *
     * Every plan receives its authoritative transition decision:
     *
     * FREE -> plan       PURCHASE
     * same plan          RENEWAL
     * higher plan        UPGRADE
     * lower plan         DOWNGRADE / blocked while active
     *
     * @return array{
     *     currentAccount:array<string, mixed>,
     *     plans:list<array<string, mixed>>
     * }
     */
    public function memberPlans(
        int $userId
    ): array {
        $currentAccount = $this
            ->membershipService
            ->resolveForUser(
                $userId
            );

        $plans = [];

        foreach (
            $this
                ->membershipService
                ->activePlans()
            as $plan
        ) {
            $planCode = mb_strtoupper(
                trim(
                    (string) (
                        $plan['code']
                        ?? ''
                    )
                )
            );

            $decision = $this
                ->purchaseService
                ->evaluate(
                    $userId,
                    $planCode
                );

            $plans[] = $this->presentPlan(
                $plan,
                $decision
            );
        }

        return [
            'currentAccount' =>
            $currentAccount,

            'plans' =>
            $plans,
        ];
    }

    /**
     * Convert one authoritative plan into view-safe presentation data.
     *
     * @param array<string, mixed> $plan
     *
     * @return array<string, mixed>
     */
    private function presentPlan(
        array $plan,
        ?MembershipPurchaseDecision $decision
    ): array {
        $code = mb_strtoupper(
            trim(
                (string) (
                    $plan['code']
                    ?? ''
                )
            )
        );

        $presentation =
            self::PRESENTATION[$code]
            ?? [
                'image' => '',
                'popular' => false,
                'description' => '',
            ];

        $pricePaise = max(
            0,
            (int) (
                $plan['pricePaise']
                ?? 0
            )
        );

        $durationMonths = max(
            0,
            (int) (
                $plan['durationMonths']
                ?? 0
            )
        );

        $priceRupees =
            $pricePaise / 100;

        $monthlyRupees =
            $durationMonths > 0
            ? $priceRupees
            / $durationMonths
            : 0;

        return [
            /*
             * Stable commercial identity.
             */
            'id' =>
            max(
                0,
                (int) (
                    $plan['id']
                    ?? 0
                )
            ),

            'code' =>
            $code,

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

            /*
             * Commercial values.
             *
             * These originate exclusively from membership_plans.
             */
            'pricePaise' =>
            $pricePaise,

            'priceDisplay' =>
            number_format(
                $priceRupees,
                0,
                '.',
                ','
            ),

            'durationMonths' =>
            $durationMonths,

            'durationDisplay' =>
            $durationMonths === 1
                ? '1 month'
                : $durationMonths
                . ' months',

            'monthlyDisplay' =>
            $durationMonths > 0
                ? '₹'
                . number_format(
                    round(
                        $monthlyRupees
                    ),
                    0,
                    '.',
                    ','
                )
                . '/month'
                : null,

            'profileViewLimit' =>
            max(
                0,
                (int) (
                    $plan['profileViewLimit']
                    ?? 0
                )
            ),

            'dailyProfileViewLimit' =>
            max(
                0,
                (int) (
                    $plan['dailyProfileViewLimit']
                    ?? 0
                )
            ),

            'liveIntroductionViewLimit' =>
            max(
                0,
                (int) (
                    $plan['liveIntroductionViewLimit']
                    ?? 0
                )
            ),

            'hasMatchManager' => ($plan['hasMatchManager'] ?? false)
                === true,

            /*
             * Presentation metadata only.
             */
            'image' =>
            (string) $presentation['image'],

            'popular' =>
            $presentation['popular'] === true,

            'description' =>
            (string) $presentation['description'],

            /*
             * Member purchase-transition state.
             *
             * null for public pricing.
             */
            'purchaseDecision' =>
            $decision?->toArray(),
        ];
    }
}
