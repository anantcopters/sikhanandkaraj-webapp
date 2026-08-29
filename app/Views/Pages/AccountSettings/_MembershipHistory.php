<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $membershipHistory
 * @var array<string, mixed> $membershipUsage
 */

$membershipHistory =
    isset($membershipHistory)
    && is_array(
        $membershipHistory
    )
    ? $membershipHistory
    : [];

$membershipUsage =
    isset($membershipUsage)
    && is_array(
        $membershipUsage
    )
    ? $membershipUsage
    : [];

$current =
    isset(
        $membershipHistory['currentMembership']
    )
    && is_array(
        $membershipHistory['currentMembership']
    )
    ? $membershipHistory['currentMembership']
    : [];

$currentMembership =
    isset(
        $current['membership']
    )
    && is_array(
        $current['membership']
    )
    ? $current['membership']
    : null;

$isPaid =
    ($current['isPaid'] ?? false)
    === true;

$accountLabel =
    trim(
        (string) (
            $current['accountLabel']
            ?? 'Free Account'
        )
    );

$memberships =
    isset(
        $membershipHistory['membershipHistory']
    )
    && is_array(
        $membershipHistory['membershipHistory']
    )
    ? $membershipHistory['membershipHistory']
    : [];

$profileUsage =
    isset(
        $membershipHistory['profileUsageHistory']
    )
    && is_array(
        $membershipHistory['profileUsageHistory']
    )
    ? $membershipHistory['profileUsageHistory']
    : [];

$videoUsage =
    isset(
        $membershipHistory['liveIntroductionUsageHistory']
    )
    && is_array(
        $membershipHistory['liveIntroductionUsageHistory']
    )
    ? $membershipHistory['liveIntroductionUsageHistory']
    : [];

/*
 * Current allowance consumption comes from
 * MemberMembershipUsageService.
 *
 * The membership-history arrays above are ledger/history collections and
 * must never be used as current allowance counters.
 */
$currentProfileUsage =
    isset(
        $membershipUsage['profileUsage']
    )
    && is_array(
        $membershipUsage['profileUsage']
    )
    ? $membershipUsage['profileUsage']
    : [];

$currentVideoUsage =
    isset(
        $membershipUsage['liveIntroductionUsage']
    )
    && is_array(
        $membershipUsage['liveIntroductionUsage']
    )
    ? $membershipUsage['liveIntroductionUsage']
    : [];

/*
 * Purchased allowance limits come from the immutable current-membership
 * snapshot resolved by MembershipService.
 */
$profileAvailable =
    max(
        0,
        (int) (
            $currentMembership['profileViewLimit']
            ?? 0
        )
    );

$dailyProfileAvailable =
    max(
        0,
        (int) (
            $currentMembership['dailyProfileViewLimit']
            ?? 0
        )
    );

$liveIntroductionAvailable =
    max(
        0,
        (int) (
            $currentMembership['liveIntroductionViewLimit']
            ?? 0
        )
    );

/*
 * Remaining allowances come exclusively from the current usage
 * presentation service.
 */
$profileRemaining =
    max(
        0,
        (int) (
            $currentProfileUsage['remaining']
            ?? 0
        )
    );

$dailyProfileRemaining =
    max(
        0,
        (int) (
            $currentProfileUsage['dailyRemaining']
            ?? 0
        )
    );

$liveIntroductionRemaining =
    max(
        0,
        (int) (
            $currentVideoUsage['remaining']
            ?? 0
        )
    );

?>

<div
    class="d-flex
        align-items-center
        gap-2
        mb-1">

    <span
        class="avatar-sm
            flex-shrink-0">

        <span
            class="avatar-title
                rounded-circle
                bg-primary-subtle
                text-primary">

            <i
                class="ri-vip-crown-line"
                aria-hidden="true">
            </i>

        </span>

    </span>

    <div>
        <h2 class="fs-18 fw-semibold mb-0">
            Membership & Usage
        </h2>

        <p class="text-muted fs-13 mb-0">
            Review your current membership, membership history
            and consumed membership allowances.
        </p>
    </div>

</div>

<hr class="my-4">

<!-- Current membership -->
<h3 class="fs-16 fw-semibold mb-3">
    Current Membership
</h3>

<div
    class="border
        rounded
        p-3
        mb-4">

    <div
        class="d-flex
            flex-column
            flex-md-row
            align-items-md-center
            justify-content-between
            gap-3">

        <div>

            <div
                class="d-flex
        align-items-center
        gap-3
        mb-1">

                <?php if (
                    $isPaid
                    && $currentMembership['planCode'] !== ''
                ): ?>

                    <?= view(
                        'Components/Membership/PlanLogo',
                        [
                            'planCode' =>
                            $currentMembership['planCode'],

                            'width' =>
                            150,
                        ]
                    ) ?>

                <?php else: ?>

                    <span class="fw-semibold fs-18">
                        <?= esc(
                            $accountLabel
                        ) ?>
                    </span>

                <?php endif; ?>

                <?php if ($isPaid): ?>

                    <span
                        class="badge
                bg-success-subtle
                text-body p-2">

                        Active
                    </span>

                <?php else: ?>

                    <span
                        class="badge
                bg-secondary-subtle
                text-body p-2">

                        Free
                    </span>

                <?php endif; ?>

            </div>

            <?php if (
                $isPaid
                && is_array(
                    $currentMembership
                )
            ): ?>

                <div class="text-muted fs-13">

                    Valid from

                    <time
                        datetime="<?= esc(
                                        $currentMembership['startsAtIso']
                                            ?? '',
                                        'attr'
                                    ) ?>">

                        <?= esc(
                            $currentMembership['startsAtDisplay']
                                ?? '—'
                        ) ?>

                    </time>

                    to

                    <time
                        datetime="<?= esc(
                                        $currentMembership['expiresAtIso']
                                            ?? '',
                                        'attr'
                                    ) ?>">

                        <?= esc(
                            $currentMembership['expiresAtDisplay']
                                ?? '—'
                        ) ?>

                    </time>

                </div>

            <?php else: ?>

                <div class="text-muted fs-13">
                    You do not currently have an active paid membership.
                </div>

            <?php endif; ?>

        </div>

        <a
            href="<?= route_to(
                        'web.account.settings.section',
                        'plans'
                    ) ?>"
            class="btn
                btn-outline-primary">

            <i
                class="ri-vip-crown-line me-1"
                aria-hidden="true">
            </i>

            <?= $isPaid
                ? 'View Plans'
                : 'Upgrade Membership' ?>

        </a>

    </div>

    <?php if (
        $isPaid
        && is_array(
            $currentMembership
        )
    ): ?>

        <div class="row g-3 mt-1">

            <div class="col-sm-6 col-xl-4">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div class="text-muted fs-12 mb-1">
                        Full Profiles
                    </div>

                    <div class="fs-18 fw-semibold">
                        <?= esc(
                            (string) $profileAvailable
                        ) ?>
                    </div>

                    <div class="text-muted fs-12">
                        Available
                    </div>

                    <div class="fs-13 mt-1">
                        <span class="fw-semibold">
                            <?= esc(
                                (string) $profileRemaining
                            ) ?>
                        </span>
                        remaining
                    </div>

                </div>

            </div>

            <div class="col-sm-6 col-xl-4">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div class="text-muted fs-12 mb-1">
                        Daily Full Profiles
                    </div>

                    <div class="fs-18 fw-semibold">
                        <?= esc(
                            (string) $dailyProfileAvailable
                        ) ?>
                    </div>

                    <div class="text-muted fs-12">
                        Available per day
                    </div>

                    <div class="fs-13 mt-1">
                        <span class="fw-semibold">
                            <?= esc(
                                (string) $dailyProfileRemaining
                            ) ?>
                        </span>
                        remaining today
                    </div>

                </div>

            </div>

            <div class="col-sm-6 col-xl-4">

                <div
                    class="border
                        rounded
                        p-3
                        h-100">

                    <div class="text-muted fs-12 mb-1">
                        Live Introductions
                    </div>

                    <div class="fs-18 fw-semibold">
                        <?= esc(
                            (string) $liveIntroductionAvailable
                        ) ?>
                    </div>

                    <div class="text-muted fs-12">
                        Available
                    </div>

                    <div class="fs-13 mt-1">
                        <span class="fw-semibold">
                            <?= esc(
                                (string) $liveIntroductionRemaining
                            ) ?>
                        </span>
                        remaining
                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<!-- Membership history -->
<h3 class="fs-16 fw-semibold mb-3">
    Membership History
</h3>

<?php if ($memberships === []): ?>

    <div
        class="border
            rounded
            text-center
            text-muted
            py-4
            mb-4">

        <i
            class="ri-vip-crown-line
                fs-24
                d-block
                mb-2"
            aria-hidden="true">
        </i>

        No paid membership history is available.

    </div>

<?php else: ?>

    <div class="table-responsive mb-4">

        <table
            class="table
                table-hover
                align-middle
                mb-0">

            <thead class="bg-info-subtle">

                <tr>
                    <th scope="col">
                        Plan
                    </th>

                    <th scope="col">
                        Amount
                    </th>

                    <th scope="col">
                        Period
                    </th>

                    <th scope="col">
                        Status
                    </th>
                </tr>

            </thead>

            <tbody>

                <?php foreach (
                    $memberships
                    as $membership
                ): ?>

                    <?php
                    if (!is_array($membership)) {
                        continue;
                    }

                    $status =
                        mb_strtoupper(
                            trim(
                                (string) (
                                    $membership['status']
                                    ?? ''
                                )
                            )
                        );

                    $statusClass =
                        match ($status) {
                            'ACTIVE' =>
                            'bg-success-subtle text-body p-2',

                            'EXPIRED' =>
                            'bg-secondary-subtle text-body p-2',

                            'REPLACED' =>
                            'bg-info-subtle text-body p-2',

                            'CANCELLED' =>
                            'bg-danger-subtle text-body p-2',

                            default =>
                            'bg-light text-muted',
                        };

                    $pricePaise =
                        max(
                            0,
                            (int) (
                                $membership['pricePaise']
                                ?? 0
                            )
                        );

                    $price =
                        $pricePaise
                        / 100;
                    ?>

                    <tr>

                        <td>

                            <div class="fw-medium">
                                <?= esc(
                                    $membership['planName']
                                        ?? 'Membership'
                                ) ?>
                            </div>

                            <div class="text-muted fs-12">
                                <?= esc(
                                    $membership['planCode']
                                        ?? ''
                                ) ?>
                            </div>

                        </td>

                        <td class="text-nowrap">
                            ₹<?= esc(
                                    number_format(
                                        $price,
                                        2
                                    )
                                ) ?>
                        </td>

                        <td>

                            <div class="fs-13">

                                <time
                                    datetime="<?= esc(
                                                    $membership['startsAtIso']
                                                        ?? '',
                                                    'attr'
                                                ) ?>">

                                    <?= esc(
                                        $membership['startsAtDisplay']
                                            ?? '—'
                                    ) ?>

                                </time>

                            </div>

                            <div class="text-muted fs-12">

                                to

                                <time
                                    datetime="<?= esc(
                                                    $membership['expiresAtIso']
                                                        ?? '',
                                                    'attr'
                                                ) ?>">

                                    <?= esc(
                                        $membership['expiresAtDisplay']
                                            ?? '—'
                                    ) ?>

                                </time>

                            </div>

                        </td>

                        <td>

                            <span
                                class="badge
                                    <?= esc(
                                        $statusClass,
                                        'attr'
                                    ) ?>">

                                <?= esc(
                                    $membership['statusLabel']
                                        ?? 'Unknown'
                                ) ?>

                            </span>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

<!--
    Verified Profile usage.

    Consumption belongs to the membership instance and candidate. Repeat
    successful openings remain visible in the history but do not consume
    another membership allowance.
-->
<h3 class="fs-16 fw-semibold mb-1">
    Verified Profile Usage
</h3>

<p class="text-muted fs-13 mb-3">
    A Verified Profile consumes membership allowance only on its first
    successful opening during that membership. Repeat openings are shown
    below but do not consume another allowance.
</p>

<?php if ($profileUsage === []): ?>

    <div
        class="
            border
            rounded
            text-center
            text-muted
            py-4
            mb-4
        ">

        <i
            class="
                ri-user-search-line
                fs-24
                d-block
                mb-2
            "
            aria-hidden="true">
        </i>

        No Verified Profile membership usage is available.

    </div>

<?php else: ?>

    <div class="table-responsive mb-4">

        <table
            class="
                table
                table-hover
                align-middle
                mb-0
            ">

            <thead class="bg-info-subtle">

                <tr>

                    <th scope="col">
                        Profile ID
                    </th>

                    <th scope="col">
                        Membership
                    </th>

                    <th scope="col">
                        First Viewed
                    </th>

                    <th scope="col">
                        Last Viewed
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php foreach (
                    $profileUsage
                    as $usage
                ): ?>

                    <?php
                    if (!is_array($usage)) {
                        continue;
                    }
                    ?>

                    <tr>

                        <td>

                            <span
                                class="
                                    badge
                                    bg-primary-subtle
                                    text-primary
                                    p-2
                                ">

                                <?= esc(
                                    $usage['profileReference']
                                        ?: '—'
                                ) ?>

                            </span>

                        </td>

                        <td>

                            <?= esc(
                                $usage['planName']
                                    ?: '—'
                            ) ?>

                        </td>

                        <!--
                            Use the usage record timestamps.

                            Do not use membership start/end dates here:
                            those describe the commercial membership period,
                            not when this candidate was actually viewed.
                        -->
                        <td class="text-nowrap">

                            <time
                                datetime="<?= esc(
                                                $usage['firstViewedAtIso']
                                                    ?? '',
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $usage['firstViewedAtDisplay']
                                        ?? '—'
                                ) ?>

                            </time>

                        </td>

                        <td class="text-nowrap">

                            <time
                                datetime="<?= esc(
                                                $usage['lastViewedAtIso']
                                                    ?? '',
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $usage['lastViewedAtDisplay']
                                        ?? '—'
                                ) ?>

                            </time>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

<!-- Live Introduction usage -->
<h3 class="fs-16 fw-semibold mb-1">
    Live Introduction Usage
</h3>

<p class="text-muted fs-13 mb-3">
    A member's Live Introduction consumes membership allowance
    only on the first successful playback during that membership.
    Replays or a replacement approved video for the same member
    do not consume another allowance.
</p>

<?php if ($videoUsage === []): ?>

    <div
        class="border
            rounded
            text-center
            text-muted
            py-4">

        <i
            class="ri-video-line
                fs-24
                d-block
                mb-2"
            aria-hidden="true">
        </i>

        No Live Introduction membership usage is available.

    </div>

<?php else: ?>

    <div class="table-responsive">

        <table
            class="table
                table-hover
                align-middle
                mb-0">

            <thead class="bg-info-subtle">

                <tr>
                    <th scope="col">
                        Profile ID
                    </th>

                    <th scope="col">
                        Membership
                    </th>

                    <th scope="col">
                        First Watched
                    </th>

                    <th scope="col">
                        Last Watched
                    </th>
                </tr>

            </thead>

            <tbody>

                <?php foreach (
                    $videoUsage
                    as $usage
                ): ?>

                    <?php
                    if (!is_array($usage)) {
                        continue;
                    }
                    ?>

                    <tr>

                        <td>

                            <span
                                class="badge
                                    bg-primary-subtle
                                    text-primary
                                    p-2">

                                <?= esc(
                                    $usage['profileReference']
                                        ?: '—'
                                ) ?>

                            </span>

                        </td>

                        <td>
                            <?= esc(
                                $usage['planName']
                                    ?: '—'
                            ) ?>
                        </td>

                        <td class="text-nowrap">

                            <time
                                datetime="<?= esc(
                                                $usage['firstViewedAtIso']
                                                    ?? '',
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $usage['firstViewedAtDisplay']
                                        ?? '—'
                                ) ?>

                            </time>

                        </td>

                        <td class="text-nowrap">

                            <time
                                datetime="<?= esc(
                                                $usage['lastViewedAtIso']
                                                    ?? '',
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $usage['lastViewedAtDisplay']
                                        ?? '—'
                                ) ?>

                            </time>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>