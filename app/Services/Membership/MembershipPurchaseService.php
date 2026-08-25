<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\MemberMembershipModel;
use App\Models\MembershipPlanModel;
use CodeIgniter\Database\BaseConnection;
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
 * When another membership is active the replacement transaction performs:
 *
 * 1. lock the member;
 * 2. lock and re-read the active membership;
 * 3. temporarily move the old membership out of ACTIVE;
 * 4. create exactly one new ACTIVE membership with fresh plan limits;
 * 5. mark/link the previous membership as REPLACED;
 * 6. retain all old usage/history;
 * 7. do not transfer unused quota;
 * 8. do not transfer remaining membership days.
 *
 * Steps 3-5 are performed inside one database transaction. This ordering is
 * intentional because the database permits only one ACTIVE membership for a
 * member at a time.
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
     * Evaluate a requested purchase against persistence.
     *
     * Suitable for callers that have not already resolved the member's
     * current membership.
     *
     * IMPORTANT:
     *
     * This result is advisory only. Activation evaluates the transition again
     * inside the activation transaction because membership state may change
     * while the member is completing payment.
     */
    public function evaluate(
        int $userId,
        string $requestedPlanCode
    ): MembershipPurchaseDecision {
        $plan = $this
            ->planModel
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
            $this
            ->membershipModel
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
     * Evaluate a plan against an already-resolved current membership.
     *
     * WHY THIS METHOD EXISTS
     * ======================
     *
     * Membership-plan presentation resolves the member account once and then
     * evaluates GO, PLUS and PRO. Calling evaluate() separately for every card
     * would unnecessarily query the active membership once per plan.
     *
     * This method therefore accepts the already-resolved commercial identity
     * and delegates to the SAME authoritative transition rules used elsewhere.
     *
     * SECURITY
     * ========
     *
     * This method is presentation/advisory only.
     *
     * It must never be used as proof that a payment may activate a membership.
     * activateAfterSuccessfulPayment() always re-reads and re-evaluates the
     * current membership under a database lock.
     */
    public function evaluateAgainstCurrentMembership(
        string $requestedPlanCode,
        ?string $currentPlanCode,
        ?int $currentMembershipId = null
    ): MembershipPurchaseDecision {
        $requestedCode =
            $this->normalizePlanCode(
                $requestedPlanCode
            );

        /*
         * The requested plan has already come from MembershipService::
         * activePlans(), but still fail closed if an unexpected code reaches
         * this service.
         */
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

        $normalizedCurrentCode =
            $currentPlanCode !== null
            ? $this->normalizePlanCode(
                $currentPlanCode
            )
            : '';

        /*
         * No current commercial plan means the member is Free.
         */
        if ($normalizedCurrentCode === '') {
            return $this->decisionFor(
                $requestedCode,
                null
            );
        }

        /*
         * Build only the minimum membership snapshot required by
         * decisionFor(). No persistence or entitlement information is being
         * reconstructed here.
         */
        return $this->decisionFor(
            $requestedCode,
            [
                'id' =>
                max(
                    0,
                    (int) (
                        $currentMembershipId
                        ?? 0
                    )
                ),

                'plan_code_snapshot' =>
                $normalizedCurrentCode,
            ]
        );
    }

    /**
     * Activate a membership after authoritative successful payment.
     *
     * During the current pre-payment-gateway phase this method may also be
     * called only by an explicitly authorized administrative/system workflow.
     *
     * PAYMENT SECURITY
     * ================
     *
     * Never call this because:
     *
     * - the browser says payment succeeded;
     * - a payment order was merely created;
     * - payment verification is pending;
     * - an unverified webhook was received.
     *
     * Future payment integration must first authoritatively verify payment.
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

        $plan = $this
            ->planModel
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

        $durationMonths = max(
            0,
            (int) (
                $plan['duration_months']
                ?? 0
            )
        );

        if ($durationMonths <= 0) {
            /*
             * Fail closed when the authoritative commercial master is
             * malformed.
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
             * Serialize every membership activation for this user.
             *
             * Locking the member handles both:
             *
             * - FREE -> Paid, where no active membership exists;
             * - Paid -> Paid replacement.
             */
            if (
                !$this
                    ->membershipModel
                    ->lockUser(
                        $userId
                    )
            ) {
                throw new RuntimeException(
                    'The member account could not be locked '
                        . 'for membership activation.'
                );
            }

            $now =
                $this->now();

            $nowUtc =
                $now->format(
                    'Y-m-d H:i:s'
                );

            /*
             * Re-read the current membership AFTER obtaining the lock.
             *
             * A purchase decision generated while the member was viewing the
             * pricing page is advisory only. The commercial state may have
             * changed before payment was completed.
             */
            $activeMembership =
                $this
                ->membershipModel
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
             * Every successful purchase/renewal/upgrade starts immediately.
             *
             * Product rule:
             *
             * - remaining days are not transferred;
             * - unused quota is not transferred;
             * - the replacement receives fresh limits from the plan master.
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

            $replacedMembershipId =
                null;

            /*
             * The database permits only one ACTIVE membership per member.
             *
             * Therefore an existing membership must first be moved out of
             * ACTIVE inside this transaction before the replacement ACTIVE
             * membership can be inserted.
             *
             * If any subsequent operation fails, the transaction rollback
             * restores the previous membership state.
             */
            if (is_array($activeMembership)) {
                $replacedMembershipId = max(
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
                    !$this
                        ->membershipModel
                        ->beginReplacement(
                            $replacedMembershipId
                        )
                ) {
                    throw new RuntimeException(
                        'The current membership could not be '
                            . 'prepared for replacement.'
                    );
                }
            }

            /*
             * Create exactly ONE replacement membership.
             *
             * Never move this call before beginReplacement(). The database
             * protects the invariant that a member can have only one ACTIVE
             * membership.
             */
            $newMembershipId =
                $this
                ->membershipModel
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
             * Preserve the immutable membership lifecycle chain:
             *
             * previous membership -> replacement membership.
             */
            if (
                $replacedMembershipId !== null
                && !$this
                    ->membershipModel
                    ->completeReplacement(
                        $replacedMembershipId,
                        $newMembershipId
                    )
            ) {
                throw new RuntimeException(
                    'The previous membership could not be '
                        . 'linked to its replacement.'
                );
            }

            if (
                $database->transStatus()
                === false
            ) {
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

            if (
                $exception
                instanceof RuntimeException
            ) {
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
     * This is the ONE transition implementation used by:
     *
     * - advisory evaluation;
     * - already-resolved presentation evaluation;
     * - authoritative payment activation.
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
                'Your current membership could not be validated. '
                    . 'Please contact support.',
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
                'Downgrading is not available while your '
                    . 'current membership is active.',
                $requestedCode,
                $currentCode,
                $currentMembershipId
            );
        }

        if ($requestedRank === $currentRank) {
            return new MembershipPurchaseDecision(
                true,
                MembershipPurchaseDecision::ACTION_RENEWAL,
                'The membership can be renewed. The new membership '
                    . 'period and allowances will start immediately.',
                $requestedCode,
                $currentCode,
                $currentMembershipId
            );
        }

        return new MembershipPurchaseDecision(
            true,
            MembershipPurchaseDecision::ACTION_UPGRADE,
            'The membership can be upgraded. The new plan '
                . 'and allowances will start immediately.',
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
     *
     * Membership persistence uses UTC consistently with the application's
     * configured timezone.
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
     * Application/database membership timestamps are UTC.
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
