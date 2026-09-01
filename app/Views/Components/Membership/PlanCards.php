<?php

declare(strict_types=1);

use App\Services\Membership\MembershipPurchaseDecision;

/**
 * Shared authoritative membership plan cards.
 *
 * Used by:
 *
 * - public membership pricing;
 * - Account Settings -> Membership Plans.
 *
 * IMPORTANT:
 *
 * This view contains NO prices, durations or allowance definitions.
 *
 * All commercial values originate from membership_plans and arrive through
 * MembershipPlanPresentationService.
 *
 * @var string|null $context
 * @var list<array<string, mixed>>|null $plans
 * @var array<string, mixed>|null $currentAccount
 */

$context =
    isset($context)
    && in_array(
        $context,
        [
            'public',
            'member',
        ],
        true
    )
    ? $context
    : 'public';

$isMemberContext =
    $context === 'member';

$developmentPurchaseEnabled =
    $isMemberContext
    && ENVIRONMENT === 'development';

$plans =
    isset($plans)
    && is_array($plans)
    ? $plans
    : [];

$currentAccount =
    isset($currentAccount)
    && is_array($currentAccount)
    ? $currentAccount
    : [];

$currentPlanCode =
    mb_strtoupper(
        trim(
            (string) (
                $currentAccount['accountType']
                ?? 'FREE'
            )
        )
    );

$currentMembership =
    isset(
        $currentAccount['membership']
    )
    && is_array(
        $currentAccount['membership']
    )
    ? $currentAccount['membership']
    : null;

$isPaid =
    ($currentAccount['isPaid'] ?? false)
    === true;

$currentMembershipExpiry =
    $currentMembership !== null
    ? trim(
        (string) (
            $currentMembership['expiresAtDisplay']
            ?? ''
        )
    )
    : '';
?>

<?php if (
    $isMemberContext
    && $isPaid
    && $currentMembership !== null
): ?>

    <!--
        Current membership summary.

        This is intentionally separate from the plan cards so the member can
        immediately understand what is active before considering an upgrade
        or future renewal.
    -->
    <div
        class="
            alert
            alert-info
            border
            border-danger
            border-opacity-25
            mb-4
        ">

        <div
            class="
                d-flex
                align-items-center
                justify-content-between
                flex-wrap
                gap-3
            ">

            <div>

                <div
                    class="
                        fs-12
                        fw-semibold
                        text-danger
                        text-uppercase
                        mb-1
                    ">
                    Current Membership
                </div>

                <div
                    class="
        d-flex
        align-items-center
        gap-2
    ">

                    <?= view(
                        'Components/Membership/PlanLogo',
                        [
                            'planCode' =>
                            $currentPlanCode,

                            'width' =>
                            120,
                        ]
                    ) ?>

                </div>

            </div>
        </div>

        <?php if (
            $currentMembershipExpiry !== ''
        ): ?>

            <div class="fs-13 text-muted mt-2">

                Your current membership remains active until

                <strong class="text-body">
                    <?= esc(
                        $currentMembershipExpiry
                    ) ?>
                </strong>.

            </div>

        <?php endif; ?>

        <div class="fs-12 text-muted mt-2">

            An upgrade or renewal starts immediately after successful
            payment. Remaining days and unused allowances from the current
            membership are not carried forward.

        </div>

        <!--
            Renewal is a valid commercial transition in the backend.

            No renewal button is exposed yet because payment integration has
            not been implemented. Once checkout exists, renewal should be a
            separate explicit action rather than making the Current Plan card
            ambiguous.
        -->

    </div>

<?php elseif ($isMemberContext): ?>

    <div
        class="
            alert
            alert-info
            border
            mb-4
        ">

        <div class="fw-semibold">
            Free Account
        </div>

        <div class="fs-13 text-muted mt-1">
            Choose a membership when paid activation becomes available.
        </div>

    </div>

<?php endif; ?>


<?php if ($plans === []): ?>

    <div
        class="
            alert
            alert-warning
            mb-0
        "
        role="status">

        <i
            class="ri-information-line me-1"
            aria-hidden="true">
        </i>

        Membership plans are currently unavailable.
        Please try again later.

    </div>

<?php else: ?>

    <div class="row gy-4 align-items-stretch">

        <?php foreach ($plans as $plan): ?>

            <?php
            /*
             * Normalize all incoming presentation data locally.
             *
             * The view must not infer or recreate commercial rules.
             */
            $planCode =
                mb_strtoupper(
                    trim(
                        (string) (
                            $plan['code']
                            ?? ''
                        )
                    )
                );

            $planName =
                trim(
                    (string) (
                        $plan['name']
                        ?? ''
                    )
                );

            $positioning =
                trim(
                    (string) (
                        $plan['positioning']
                        ?? ''
                    )
                );

            $image =
                trim(
                    (string) (
                        $plan['image']
                        ?? ''
                    )
                );

            $priceDisplay =
                trim(
                    (string) (
                        $plan['priceDisplay']
                        ?? '0'
                    )
                );

            $durationDisplay =
                trim(
                    (string) (
                        $plan['durationDisplay']
                        ?? ''
                    )
                );

            $monthlyDisplay =
                isset(
                    $plan['monthlyDisplay']
                )
                ? trim(
                    (string) $plan['monthlyDisplay']
                )
                : '';

            $profileViewLimit =
                max(
                    0,
                    (int) (
                        $plan['profileViewLimit']
                        ?? 0
                    )
                );

            $dailyProfileViewLimit =
                max(
                    0,
                    (int) (
                        $plan['dailyProfileViewLimit']
                        ?? 0
                    )
                );

            $liveIntroductionViewLimit =
                max(
                    0,
                    (int) (
                        $plan['liveIntroductionViewLimit']
                        ?? 0
                    )
                );

            $hasMatchManager =
                ($plan['hasMatchManager']
                    ?? false)
                === true;

            $popular =
                ($plan['popular']
                    ?? false)
                === true;

            $description =
                trim(
                    (string) (
                        $plan['description']
                        ?? ''
                    )
                );

            $decision =
                isset(
                    $plan['purchaseDecision']
                )
                && is_array(
                    $plan['purchaseDecision']
                )
                ? $plan['purchaseDecision']
                : null;

            $isCurrentPlan =
                $isMemberContext
                && $isPaid
                && $currentPlanCode !== ''
                && $currentPlanCode
                === $planCode;

            $action =
                $decision !== null
                ? mb_strtoupper(
                    trim(
                        (string) (
                            $decision['action']
                            ?? ''
                        )
                    )
                )
                : '';

            $allowed =
                $decision !== null
                && (
                    $decision['allowed']
                    ?? false
                ) === true;

            /*
             * Payment gateway is intentionally not wired yet.
             *
             * These labels describe the authoritative commercial transition.
             * They must never activate a membership themselves.
             */
            $buttonLabel =
                match ($action) {
                    MembershipPurchaseDecision::ACTION_RENEWAL =>
                    'Renew ' . $planCode,

                    MembershipPurchaseDecision::ACTION_UPGRADE =>
                    'Upgrade to ' . $planCode,

                    MembershipPurchaseDecision::ACTION_DOWNGRADE =>
                    'Downgrade Unavailable',

                    MembershipPurchaseDecision::ACTION_PURCHASE =>
                    'Choose ' . $planCode,

                    default =>
                    'Unavailable',
                };

            /*
             * Current plan remains visually distinct.
             *
             * The underlying purchase decision may correctly be RENEWAL, but
             * renewal will receive a separate explicit action once payment is
             * implemented.
             */
            if ($isCurrentPlan) {
                $buttonLabel =
                    'Current Plan';
            }
            ?>

            <div class="col-12 col-lg-4">

                <article
                    class="
                        card
                        border
                        border-danger
                        <?= $popular
                            ? ''
                            : 'border-opacity-25' ?>
                        <?= $popular
                            ? 'shadow'
                            : 'shadow-sm' ?>
                        h-100
                        mb-0
                        <?= $popular
                            ? 'ribbon-box left'
                            : '' ?>
                    ">

                    <div
                        class="
                            card-body
                            p-4
                            d-flex
                            flex-column
                        ">

                        <?php if ($popular): ?>

                            <div
                                class="
                                    ribbon-two
                                    ribbon-two-danger
                                ">

                                <span>
                                    Popular
                                </span>

                            </div>

                        <?php endif; ?>

                        <div class="text-center mb-0">

                            <p
                                class="
                                    fs-12
                                    fw-semibold
                                    text-danger
                                    text-uppercase
                                    mb-3
                                ">

                                <?= esc(
                                    $positioning
                                ) ?>

                            </p>

                            <?php if ($image !== ''): ?>

                                <img
                                    src="<?= base_url(
                                                'assets/images/'
                                                    . $image
                                            ) ?>"
                                    alt="<?= esc(
                                                $planName,
                                                'attr'
                                            ) ?>"
                                    class="img-fluid"
                                    width="200"
                                    height="90"
                                    loading="lazy">

                            <?php else: ?>

                                <h3 class="fs-22 fw-semibold">

                                    <?= esc(
                                        $planName
                                    ) ?>

                                </h3>

                            <?php endif; ?>

                        </div>

                        <div class="text-center py-3">

                            <div class="mb-1">

                                <span
                                    class="
                                        fs-18
                                        fw-semibold
                                        align-top
                                    ">
                                    ₹
                                </span>

                                <span
                                    class="
                                        fs-36
                                        fw-bold
                                        ff-secondary
                                    ">

                                    <?= esc(
                                        $priceDisplay
                                    ) ?>

                                </span>

                            </div>

                            <p class="text-muted mb-1">

                                for

                                <?= esc(
                                    $durationDisplay
                                ) ?>

                            </p>

                            <?php if (
                                $monthlyDisplay !== ''
                            ): ?>

                                <p
                                    class="
                                        fs-13
                                        <?= $popular
                                            ? 'text-danger fw-semibold'
                                            : 'text-muted' ?>
                                        mb-0
                                    ">

                                    <?= esc(
                                        $monthlyDisplay
                                    ) ?>

                                </p>

                            <?php endif; ?>

                        </div>

                        <?php if (
                            $description !== ''
                        ): ?>

                            <p
                                class="
                                    fs-13
                                    text-muted
                                    text-center
                                    mb-3
                                ">

                                <?= esc(
                                    $description
                                ) ?>

                            </p>

                        <?php endif; ?>

                        <div
                            class="
                                border-top
                                pt-3
                                mt-1
                            ">

                            <ul
                                class="
                                    list-unstyled
                                    vstack
                                    gap-3
                                    mb-0
                                ">

                                <li>

                                    <div
                                        class="
                                            d-flex
                                            align-items-start
                                            gap-2
                                        ">

                                        <i
                                            class="
                                                ri-shield-check-line
                                                text-success
                                                fs-18
                                                flex-shrink-0
                                            "
                                            aria-hidden="true">
                                        </i>

                                        <div>

                                            <strong>

                                                <?= esc(
                                                    (string)
                                                    $profileViewLimit
                                                ) ?>

                                                Verified Profiles

                                            </strong>

                                            <div
                                                class="
                                                    fs-12
                                                    text-muted
                                                    mt-1
                                                ">

                                                Up to

                                                <?= esc(
                                                    (string)
                                                    $dailyProfileViewLimit
                                                ) ?>

                                                per day

                                            </div>

                                        </div>

                                    </div>

                                </li>

                                <li>

                                    <div
                                        class="
            d-flex
            align-items-start
            gap-2
        ">

                                        <i
                                            class="
                ri-video-line
                text-success
                fs-18
                flex-shrink-0
            "
                                            aria-hidden="true">
                                        </i>

                                        <div>

                                            <!--
                The allowance itself is authoritative and comes from the
                membership plan master.

                Do not repeat the numeric allowance in secondary copy. The
                primary line already communicates the complete entitlement.
            -->
                                            <strong>

                                                <?= esc(
                                                    (string)
                                                    $liveIntroductionViewLimit
                                                ) ?>

                                                Live Introduction Views

                                            </strong>

                                            <div
                                                class="
                    fs-12
                    text-muted
                    mt-1
                ">

                                                Included during your membership

                                            </div>

                                        </div>

                                    </div>

                                </li>

                                <?php if (
                                    $hasMatchManager
                                ): ?>

                                    <li>

                                        <div
                                            class="
                                                d-flex
                                                align-items-start
                                                gap-2
                                            ">

                                            <i
                                                class="
                                                    ri-customer-service-2-line
                                                    text-success
                                                    fs-18
                                                    flex-shrink-0
                                                "
                                                aria-hidden="true">
                                            </i>

                                            <div>

                                                <strong>
                                                    Dedicated Match Manager
                                                </strong>

                                                <div
                                                    class="
                                                        fs-12
                                                        text-muted
                                                        mt-1
                                                    ">

                                                    Personalised assistance
                                                    throughout your membership

                                                </div>

                                            </div>

                                        </div>

                                    </li>

                                <?php else: ?>

                                    <li>

                                        <div
                                            class="
                                                d-flex
                                                align-items-center
                                                gap-2
                                            ">

                                            <i
                                                class="
                                                    ri-checkbox-circle-line
                                                    text-success
                                                    fs-18
                                                    flex-shrink-0
                                                "
                                                aria-hidden="true">
                                            </i>

                                            <strong>
                                                All essential features
                                            </strong>

                                        </div>

                                    </li>

                                <?php endif; ?>

                            </ul>

                        </div>

                        <div class="mt-auto pt-4">

                            <?php if (
                                $isMemberContext
                            ): ?>

                                <?php if (
                                    $isCurrentPlan
                                    && $action
                                    ===
                                    MembershipPurchaseDecision::ACTION_RENEWAL
                                ): ?>

                                    <div
                                        class="
                                            d-flex
                                            align-items-center
                                            justify-content-center
                                            gap-1
                                            text-success
                                            fw-semibold
                                            mb-2
                                        ">

                                        <i
                                            class="ri-checkbox-circle-line"
                                            aria-hidden="true">
                                        </i>

                                        Current Plan

                                    </div>

                                    <?php if (
                                        $developmentPurchaseEnabled
                                    ): ?>

                                        <form
                                            method="post"
                                            action="<?= route_to(
                                                        'web.membership.purchase'
                                                    ) ?>">

                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="plan_code"
                                                value="<?= esc(
                                                            $planCode,
                                                            'attr'
                                                        ) ?>">

                                            <button
                                                type="submit"
                                                class="
                                                    btn
                                                    <?= $popular
                                                        ? 'btn-danger'
                                                        : 'btn-outline-danger' ?>
                                                    w-100
                                                ">

                                                Renew
                                                <?= esc(
                                                    $planCode
                                                ) ?>

                                            </button>

                                        </form>

                                        <div
                                            class="
                                                fs-12
                                                text-muted
                                                text-center
                                                mt-2
                                            ">
                                            Development payment simulation
                                        </div>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="
                                                btn
                                                btn-success
                                                w-100
                                            "
                                            disabled>

                                            <i
                                                class="
                                                    ri-checkbox-circle-line
                                                    me-1
                                                "
                                                aria-hidden="true">
                                            </i>

                                            Current Plan

                                        </button>

                                    <?php endif; ?>

                                <?php elseif (
                                    !$allowed
                                ): ?>

                                    <button
                                        type="button"
                                        class="
                                            btn
                                            btn-outline-secondary
                                            w-100
                                        "
                                        disabled>

                                        <i
                                            class="
                                                ri-lock-2-line
                                                me-1
                                            "
                                            aria-hidden="true">
                                        </i>

                                        <?= esc(
                                            $buttonLabel
                                        ) ?>

                                    </button>

                                <?php elseif (
                                    $developmentPurchaseEnabled
                                ): ?>

                                    <!-- <form
                                        method="post"
                                        action="<//?= route_to(
                                                   // 'web.membership.purchase'
                                                //) ?>">

                                        <//?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="plan_code"
                                            value="<//?= esc(
                                                        //$planCode,
                                                        'attr'
                                                    ) ?>">

                                        <button
                                            type="submit"
                                            class="
                                                btn
                                                <//?= $popular
                                                    ? 'btn-danger'
                                                    : 'btn-outline-danger' ?>
                                                w-100
                                            ">

                                            <//?= esc(
                                                $buttonLabel
                                            ) ?>

                                        </button>

                                    </form> -->

                                    <div
                                        class="
                                            fs-12
                                            text-muted
                                            text-center
                                            mt-2
                                        ">
                                        Development payment simulation
                                    </div>

                                <?php else: ?>

                                    <button
                                        type="button"
                                        class="
                                            btn
                                            <?= $popular
                                                ? 'btn-danger'
                                                : 'btn-outline-danger' ?>
                                            w-100
                                        "
                                        disabled>

                                        <?= esc(
                                            $buttonLabel
                                        ) ?>

                                    </button>

                                    <div
                                        class="
                                            fs-12
                                            text-muted
                                            text-center
                                            mt-2
                                        ">
                                        Online purchase coming soon
                                    </div>

                                <?php endif; ?>

                                <?php if (
                                    !$isCurrentPlan
                                    && $decision !== null
                                    && trim(
                                        (string) (
                                            $decision['message']
                                            ?? ''
                                        )
                                    ) !== ''
                                ): ?>

                                    <div
                                        class="
                                            fs-12
                                            text-muted
                                            text-center
                                            mt-2
                                        ">

                                        <?= esc(
                                            (string)
                                            $decision['message']
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            <?php else: ?>

                                <a
                                    href="<?= esc(
                                                route_to('web.home')
                                                    . '#registration',
                                                'attr'
                                            ) ?>"
                                    class="
            btn
            <?= $popular
                                    ? 'btn-danger'
                                    : 'btn-outline-danger' ?>
            w-100
        ">

                                    Choose
                                    <?= esc(
                                        $planCode
                                    ) ?>

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<div
    class="
        text-center
        border-top
        mt-4
        pt-4
    ">

    <p
        class="
            fs-13
            text-muted
            lh-lg
            mb-2
        ">

        <strong class="text-body">
            All paid plans include:
        </strong>

        Browse Profiles · Send Interests ·
        Shortlist · Advanced Search ·
        Preference Match Count ·

        <span class="fw-medium text-primary">
            Mobile, Email &amp; Aadhaar Verification ·
            Live Introduction
        </span>

    </p>

    <p
        class="
            fs-12
            text-muted
            lh-lg
            mb-2
        ">

        <strong class="text-body">
            Verified Profile:
        </strong>

        A profile with at least one verified credential —
        Mobile, Email, Aadhaar or Live Introduction.

    </p>

    <p
        class="
            fs-13
            fw-semibold
            mb-0
        ">

        All prices are inclusive of GST.
        No hidden charges.

    </p>

</div>