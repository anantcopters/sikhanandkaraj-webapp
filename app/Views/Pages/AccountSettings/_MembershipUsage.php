<?php

declare(strict_types=1);

/**
 * Member-facing current membership allowance summary.
 *
 * Detailed membership and consumption history is intentionally rendered by
 * _MembershipHistory.php. This partial owns only the current membership
 * counters so the same history is not presented twice.
 *
 * Expected view data:
 *
 * @var array<string, mixed> $membershipUsage
 *
 * Keep normalization local to this partial so the template remains safe when
 * an optional presentation key is absent. Commercial values themselves remain
 * authoritative in the membership presentation/service layer.
 */

$membershipUsage =
    isset($membershipUsage)
    && is_array($membershipUsage)
    ? $membershipUsage
    : [];

$isPaid =
    ($membershipUsage['isPaid'] ?? false)
    === true;

$membership =
    isset($membershipUsage['membership'])
    && is_array(
        $membershipUsage['membership']
    )
    ? $membershipUsage['membership']
    : null;

$profileUsage =
    isset($membershipUsage['profileUsage'])
    && is_array(
        $membershipUsage['profileUsage']
    )
    ? $membershipUsage['profileUsage']
    : [];

$liveIntroductionUsage =
    isset(
        $membershipUsage['liveIntroductionUsage']
    )
    && is_array(
        $membershipUsage['liveIntroductionUsage']
    )
    ? $membershipUsage['liveIntroductionUsage']
    : [];

/*
 * Normalize current Full Profile allowance counters.
 *
 * Repeat access to an already consumed candidate is handled by the usage
 * service and therefore does not need any special business logic here.
 */
$profileUsed =
    max(
        0,
        (int) (
            $profileUsage['used']
            ?? 0
        )
    );

$profileLimit =
    max(
        0,
        (int) (
            $profileUsage['limit']
            ?? 0
        )
    );

$profileRemaining =
    max(
        0,
        (int) (
            $profileUsage['remaining']
            ?? 0
        )
    );

$profileUsedToday =
    max(
        0,
        (int) (
            $profileUsage['usedToday']
            ?? 0
        )
    );

$profileDailyLimit =
    max(
        0,
        (int) (
            $profileUsage['dailyLimit']
            ?? 0
        )
    );

$profileDailyRemaining =
    max(
        0,
        (int) (
            $profileUsage['dailyRemaining']
            ?? 0
        )
    );

/*
 * Normalize the current Live Introduction allowance counters.
 *
 * Detailed playback history remains owned by _MembershipHistory.php.
 */
$liveIntroductionUsed =
    max(
        0,
        (int) (
            $liveIntroductionUsage['used']
            ?? 0
        )
    );

$liveIntroductionLimit =
    max(
        0,
        (int) (
            $liveIntroductionUsage['limit']
            ?? 0
        )
    );

$liveIntroductionRemaining =
    max(
        0,
        (int) (
            $liveIntroductionUsage['remaining']
            ?? 0
        )
    );

$planName =
    $membership !== null
    ? trim(
        (string) (
            $membership['planName']
            ?? $membership['planCode']
            ?? ''
        )
    )
    : '';
?>

<div class="mb-4">
    <div
        class="
            d-flex
            align-items-center
            justify-content-between
            flex-wrap
            gap-2
        ">

        <div>
            <h3 class="fs-16 fw-semibold mb-1">
                Membership Usage
            </h3>

            <p class="text-muted fs-13 mb-0">
                Track the usage included with your current membership.
            </p>
        </div>

        <?php if (
            $isPaid
            && $membership['planCode'] !== ''
        ): ?>

            <div class="flex-shrink-0">

                <?= view(
                    'Components/Membership/PlanLogo',
                    [
                        'planCode' =>
                        $membership['planCode'],

                        'width' =>
                        150,
                    ]
                ) ?>

            </div>

        <?php endif; ?>

    </div>
</div>

<?php if (!$isPaid): ?>

    <div
        class="alert alert-info mb-0"
        role="status">

        <i
            class="ri-information-line me-1"
            aria-hidden="true">
        </i>

        Membership usage becomes available when you activate
        a paid membership.

    </div>

<?php else: ?>

    <div class="row g-3">

        <!--
            Verified Profile allowance.

            Repeat access to the same candidate during the same membership
            does not consume another allowance.
        -->
        <div class="col-12 col-lg-6">

            <div class="border rounded p-3 h-100">

                <div
                    class="
                        d-flex
                        align-items-center
                        gap-2
                        mb-3
                    ">

                    <i
                        class="ri-user-search-line fs-18 text-success"
                        aria-hidden="true">
                    </i>

                    <h4 class="fs-15 fw-semibold mb-0">
                        Verified Profiles
                    </h4>

                </div>

                <div class="row g-3">

                    <div class="col-6">

                        <div class="text-muted fs-12">
                            Membership Usage
                        </div>

                        <div class="fw-semibold">
                            <?= esc(
                                (string) $profileUsed
                            ) ?>
                            /
                            <?= esc(
                                (string) $profileLimit
                            ) ?>
                        </div>

                    </div>

                    <div class="col-6">

                        <div class="text-muted fs-12">
                            Remaining
                        </div>

                        <div class="fw-semibold">
                            <?= esc(
                                (string) $profileRemaining
                            ) ?>
                        </div>

                    </div>

                    <div class="col-6">

                        <div class="text-muted fs-12">
                            Used Today
                        </div>

                        <div class="fw-semibold">
                            <?= esc(
                                (string) $profileUsedToday
                            ) ?>
                            /
                            <?= esc(
                                (string) $profileDailyLimit
                            ) ?>
                        </div>

                    </div>

                    <div class="col-6">

                        <div class="text-muted fs-12">
                            Remaining Today
                        </div>

                        <div class="fw-semibold">
                            <?= esc(
                                (string) $profileDailyRemaining
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!--
            Live Introduction allowance.

            Consumption is candidate-scoped, not video-version scoped.
        -->
        <div class="col-12 col-lg-6">

            <div class="border rounded p-3 h-100">

                <div
                    class="
                        d-flex
                        align-items-center
                        gap-2
                        mb-3
                    ">

                    <i
                        class="ri-video-line fs-18 text-danger"
                        aria-hidden="true">
                    </i>

                    <h4 class="fs-15 fw-semibold mb-0">
                        Live Introductions
                    </h4>

                </div>

                <div class="row g-3">

                    <div class="col-6">

                        <div class="text-muted fs-12">
                            Membership Usage
                        </div>

                        <div class="fw-semibold">
                            <?= esc(
                                (string) $liveIntroductionUsed
                            ) ?>
                            /
                            <?= esc(
                                (string) $liveIntroductionLimit
                            ) ?>
                        </div>

                    </div>

                    <div class="col-6">

                        <div class="text-muted fs-12">
                            Remaining
                        </div>

                        <div class="fw-semibold">
                            <?= esc(
                                (string) $liveIntroductionRemaining
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>