<?php

declare(strict_types=1);

namespace App\Services\Membership;

use CodeIgniter\Database\BaseConnection;

use App\Models\MemberMembershipModel;
use App\Models\MembershipPlanModel;
use RuntimeException;
use Throwable;

/**
 * Authoritative purchase / renewal / upgrade semantics.
 *
 * PRODUCT RULES
 * =============
 *
 * FREE -> GO/PLUS/PRO
 *     PURCHASE
 *
 * GO -> GO
 * PLUS -> PLUS
 * PRO -> PRO
 *     RENEWAL
 *
 * GO -> PLUS/PRO
 * PLUS -> PRO
 *     UPGRADE
 *
 * PLUS -> GO
 * PRO -> PLUS/GO
 *     BLOCKED DOWNGRADE while current membership is active.
 *
 * Any purchase after the previous membership has expired is treated as a
 * fresh PURCHASE because the member is Free at that point.
 *
 * ACTIVATION RULE
 * ===============
 *
 * A successfully paid/authorized membership starts immediately.
 *
 * When another membership is active:
 *
 * 1. create the new membership with fresh plan limits;
 * 2. mark the previous membership REPLACED;
 * 3. point replaced_by_membership_id to the new membership;
 * 4. retain all old usage/history;
 * 5. do not transfer unused quota;
 * 6. do not transfer remaining membership days.
 *
 * PAYMENT BOUNDARY
 * ================
 *
 * This service does NOT process payment.
 *
 * Future payment integration must call activateAfterSuccessfulPayment()
 * only after payment has been authoritatively verified.
 */
final class MembershipPurchaseService
{
    /**
     * Stable hierarchy used only for purchase transition validation.
     *
     * Product capabilities themselves remain entitlement-driven.
     */
    private const PLAN_RANK = [
        MembershipPlanModel::CODE_GO =>
        10,

        MembershipPlanModel::CODE_PLUS =>
        20,

        MembershipPlanModel::CODE_PRO =>
        30,
    ];

    public function __construct(
        private readonly BaseConnection
        $database,

        private readonly MembershipPlanModel
        $planModel,

        private readonly MemberMembershipModel
        $membershipModel
    ) {}

    /**
     * Evaluate a requested purchase without changing persistence.
     *
     * This is suitable for:
     *
     * - plan-card presentation;
     * - future payment-order creation;
     * - displaying upgrade/renewal wording.
     *
     * IMPORTANT:
     *
     * This result is advisory only. Activation evaluates the transition again
     * under a database lock.
     */
    public function evaluate(
        int $userId,
        string $requestedPlanCode
    ): MembershipPurchaseDecision {
        $plan =
            $this->planModel
            ->findActiveByCode(
                $requestedPlanCode
            );

        if (!is_array($plan)) {
            return new MembershipPurchaseDecision(
                false,
                MembershipPurchaseDecision::ACTION_UNAVAILABLE,
                'The selected membership plan is not currently available.',
                $this->normalizePlanCode(
                    $requestedPlanCode
                )
            );
        }

        $requestedCode =
            $this->normalizePlanCode(
                (string) (
                    $plan['code']
                    ?? ''
                )
            );

        if ($userId <= 0) {
            return new MembershipPurchaseDecision(
                false,
                MembershipPurchaseDecision::ACTION_UNAVAILABLE,
                'A valid member account is required to purchase a membership.',
                $requestedCode
            );
        }

        $activeMembership =
            $this->membershipModel
            ->activeForUser(
                $userId,
                $this->nowUtc()
            );

        return $this->decisionFor(
            $requestedCode,
            $activeMembership
        );
    }

    /**
     * Activate a membership after authoritative successful payment.
     *
     * During the current pre-payment-gateway phase this method may also be
     * called by an explicitly authorized administrative/system activation
     * workflow.
     *
     * DO NOT call this when:
     *
     * - a payment order is merely created;
     * - the browser claims payment succeeded;
     * - payment verification is pending;
     * - a webhook signature has not been verified.
     *
     * @throws RuntimeException
     */
    public function activateAfterSuccessfulPayment(
        int $userId,
        string $requestedPlanCode
    ): MembershipActivationResult {
        if ($userId <= 0) {
            throw new RuntimeException(
                'A valid member is required for membership activation.'
            );
        }

        $plan =
            $this->planModel
            ->findActiveByCode(
                $requestedPlanCode
            );

        if (!is_array($plan)) {
            throw new RuntimeException(
                'The selected membership plan is not currently available.'
            );
        }

        $requestedCode =
            $this->normalizePlanCode(
                (string) (
                    $plan['code']
                    ?? ''
                )
            );

        $durationMonths =
            max(
                0,
                (int) (
                    $plan['duration_months']
                    ?? 0
                )
            );

        if ($durationMonths <= 0) {
            /*
             * Fail closed if the commercial master is malformed.
             */
            throw new RuntimeException(
                'The selected membership plan has an invalid duration.'
            );
        }

        $database =
            $this->database;

        $database->transBegin();

        try {
            /*
             * Always lock the member first.
             *
             * This protects both:
             *
             * - Free -> Paid activation where no membership row exists yet;
             * - Paid -> Paid replacement.
             */
            if (
                !$this->membershipModel
                    ->lockUser(
                        $userId
                    )
            ) {
                throw new RuntimeException(
                    'The member account could not be locked for membership activation.'
                );
            }

            $now =
                $this->now();

            $nowUtc =
                $now->format(
                    'Y-m-d H:i:s'
                );

            /*
             * Re-read the active membership after obtaining the member lock.
             *
             * Never trust the state that existed when checkout began.
             */
            $activeMembership =
                $this->membershipModel
                ->lockActiveForUser(
                    $userId,
                    $nowUtc
                );

            $decision =
                $this->decisionFor(
                    $requestedCode,
                    $activeMembership
                );

            if (!$decision->allowed) {
                throw new RuntimeException(
                    $decision->message
                );
            }

            /*
             * Product rule:
             *
             * Every successful activation begins immediately.
             *
             * We intentionally do NOT append duration to the old expiry date.
             * Therefore a renewal made while the old plan is active replaces
             * the old membership and begins a fresh membership period now.
             */
            $startsAt =
                $nowUtc;

            $expiresAt =
                $now
                ->modify(
                    '+'
                        . $durationMonths
                        . ' months'
                )
                ->format(
                    'Y-m-d H:i:s'
                );

            /*
             * Create the new membership first so the old historical record can
             * retain an explicit replaced_by_membership_id reference.
             */
            $newMembershipId =
                $this->membershipModel
                ->createFromPlan(
                    $userId,
                    $plan,
                    $startsAt,
                    $expiresAt
                );

            if ($newMembershipId <= 0) {
                throw new RuntimeException(
                    'The new membership could not be created.'
                );
            }

            $replacedMembershipId =
                null;

            $replacedMembershipId =
                null;

            /*
            * If another membership is active, remove it from ACTIVE before inserting
            * the replacement.
            *
            * This is safe because:
            *
            * - the entire operation is transactional;
            * - the member row is locked;
            * - the current membership row is locked;
            * - rollback restores ACTIVE if anything later fails;
            * - the database partial unique index prevents duplicate ACTIVE rows.
            */
            if (is_array($activeMembership)) {
                $replacedMembershipId =
                    max(
                        0,
                        (int) (
                            $activeMembership['id']
                            ?? 0
                        )
                    );

                if ($replacedMembershipId <= 0) {
                    throw new RuntimeException(
                        'The current membership record is invalid.'
                    );
                }

                if (
                    !$this->membershipModel
                        ->beginReplacement(
                            $replacedMembershipId
                        )
                ) {
                    throw new RuntimeException(
                        'The current membership could not be prepared for replacement.'
                    );
                }
            }

            /*
            * The new membership receives a completely fresh commercial snapshot and
            * fresh usage allowances.
            *
            * Nothing from the old membership is carried into this membership.
            */
            $newMembershipId =
                $this->membershipModel
                ->createFromPlan(
                    $userId,
                    $plan,
                    $startsAt,
                    $expiresAt
                );

            if ($newMembershipId <= 0) {
                throw new RuntimeException(
                    'The new membership could not be created.'
                );
            }

            /*
            * Preserve an explicit historical chain:
            *
            * old membership -> new membership
            */
            if (
                $replacedMembershipId !== null
                && !$this->membershipModel
                    ->completeReplacement(
                        $replacedMembershipId,
                        $newMembershipId
                    )
            ) {
                throw new RuntimeException(
                    'The previous membership could not be linked to its replacement.'
                );
            }

            if ($database->transStatus() === false) {
                throw new RuntimeException(
                    'Membership activation transaction failed.'
                );
            }

            $database->transCommit();

            return new MembershipActivationResult(
                $newMembershipId,
                $decision->action,
                $requestedCode,
                $startsAt,
                $expiresAt,
                $replacedMembershipId
            );
        } catch (Throwable $exception) {
            $database->transRollback();

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException(
                'Membership activation failed.',
                0,
                $exception
            );
        }
    }

    /**
     * Resolve the commercial transition.
     *
     * @param array<string, mixed>|null $activeMembership
     */
    private function decisionFor(
        string $requestedCode,
        ?array $activeMembership
    ): MembershipPurchaseDecision {
        if (
            !isset(
                self::PLAN_RANK[$requestedCode]
            )
        ) {
            return new MembershipPurchaseDecision(
                false,
                MembershipPurchaseDecision::ACTION_UNAVAILABLE,
                'The selected membership plan is not available.',
                $requestedCode
            );
        }

        /*
         * No usable membership means the member is currently Free.
         *
         * An expired/replaced/cancelled historical membership has no effect on
         * the new purchase.
         */
        if (!is_array($activeMembership)) {
            return new MembershipPurchaseDecision(
                true,
                MembershipPurchaseDecision::ACTION_PURCHASE,
                'The membership can be purchased.',
                $requestedCode
            );
        }

        $currentCode =
            $this->normalizePlanCode(
                (string) (
                    $activeMembership['plan_code_snapshot']
                    ?? ''
                )
            );

        $currentMembershipId =
            max(
                0,
                (int) (
                    $activeMembership['id']
                    ?? 0
                )
            );

        if (
            !isset(
                self::PLAN_RANK[$currentCode]
            )
        ) {
            /*
             * Fail closed instead of replacing an unknown/corrupt commercial
             * membership.
             */
            return new MembershipPurchaseDecision(
                false,
                MembershipPurchaseDecision::ACTION_UNAVAILABLE,
                'Your current membership could not be validated. Please contact support.',
                $requestedCode,
                $currentCode,
                $currentMembershipId
            );
        }

        $currentRank =
            self::PLAN_RANK[$currentCode];

        $requestedRank =
            self::PLAN_RANK[$requestedCode];

        if ($requestedRank < $currentRank) {
            return new MembershipPurchaseDecision(
                false,
                MembershipPurchaseDecision::ACTION_DOWNGRADE,
                'Downgrading is not available while your current membership is active.',
                $requestedCode,
                $currentCode,
                $currentMembershipId
            );
        }

        if ($requestedRank === $currentRank) {
            return new MembershipPurchaseDecision(
                true,
                MembershipPurchaseDecision::ACTION_RENEWAL,
                'The membership can be renewed. The new membership period and allowances will start immediately.',
                $requestedCode,
                $currentCode,
                $currentMembershipId
            );
        }

        return new MembershipPurchaseDecision(
            true,
            MembershipPurchaseDecision::ACTION_UPGRADE,
            'The membership can be upgraded. The new plan and allowances will start immediately.',
            $requestedCode,
            $currentCode,
            $currentMembershipId
        );
    }

    /**
     * Normalize a stable commercial plan code.
     */
    private function normalizePlanCode(
        string $planCode
    ): string {
        return mb_strtoupper(
            trim(
                $planCode
            )
        );
    }

    /**
     * Current UTC DateTime.
     */
    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            'now',
            new \DateTimeZone(
                'UTC'
            )
        );
    }

    /**
     * Application/database timestamps are UTC.
     */
    private function nowUtc(): string
    {
        return $this
            ->now()
            ->format(
                'Y-m-d H:i:s'
            );
    }
}
