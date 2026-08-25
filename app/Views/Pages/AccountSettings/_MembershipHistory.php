<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var array<string, mixed> $membershipHistory
 */

$membershipHistory =
    isset($membershipHistory)
    && is_array(
        $membershipHistory
    )
    ? $membershipHistory
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
 * Reuse the project's normal UTC -> display timezone formatting.
 */
$formatDateTime =
    static function (
        mixed $value
    ): string {
        return DateDisplay::formatUtcDateTime(
            $value,
            '—'
        );
    };

$isoDateTime =
    static function (
        mixed $value
    ): string {
        return DateDisplay::utcToDisplayIso(
            $value
        );
    };
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
                    gap-2
                    mb-1">

                <span class="fw-semibold fs-18">
                    <?= esc(
                        $accountLabel
                    ) ?>
                </span>

                <?php if ($isPaid): ?>

                    <span
                        class="badge
                            bg-success-subtle
                            text-success">

                        Active
                    </span>

                <?php else: ?>

                    <span
                        class="badge
                            bg-secondary-subtle
                            text-secondary">

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
                                        $isoDateTime(
                                            $currentMembership['startsAt']
                                                ?? null
                                        ),
                                        'attr'
                                    ) ?>">

                        <?= esc(
                            $formatDateTime(
                                $currentMembership['startsAt']
                                    ?? null
                            )
                        ) ?>

                    </time>

                    to

                    <time
                        datetime="<?= esc(
                                        $isoDateTime(
                                            $currentMembership['expiresAt']
                                                ?? null
                                        ),
                                        'attr'
                                    ) ?>">

                        <?= esc(
                            $formatDateTime(
                                $currentMembership['expiresAt']
                                    ?? null
                            )
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

                    <div class="fw-semibold fs-18">
                        <?= esc(
                            (string) (
                                $currentMembership['profileViewLimit']
                                ?? 0
                            )
                        ) ?>
                    </div>

                    <div class="text-muted fs-12">
                        Membership allowance
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

                    <div class="fw-semibold fs-18">
                        <?= esc(
                            (string) (
                                $currentMembership['dailyProfileViewLimit']
                                ?? 0
                            )
                        ) ?>
                    </div>

                    <div class="text-muted fs-12">
                        Daily allowance
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

                    <div class="fw-semibold fs-18">
                        <?= esc(
                            (string) (
                                $currentMembership['liveIntroductionViewLimit']
                                ?? 0
                            )
                        ) ?>
                    </div>

                    <div class="text-muted fs-12">
                        Membership allowance
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
                            'bg-success-subtle text-success',

                            'EXPIRED' =>
                            'bg-secondary-subtle text-secondary',

                            'REPLACED' =>
                            'bg-info-subtle text-info',

                            'CANCELLED' =>
                            'bg-danger-subtle text-danger',

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
                                                    $isoDateTime(
                                                        $membership['startsAt']
                                                            ?? null
                                                    ),
                                                    'attr'
                                                ) ?>">

                                    <?= esc(
                                        $formatDateTime(
                                            $membership['startsAt']
                                                ?? null
                                        )
                                    ) ?>

                                </time>

                            </div>

                            <div class="text-muted fs-12">

                                to

                                <time
                                    datetime="<?= esc(
                                                    $isoDateTime(
                                                        $membership['expiresAt']
                                                            ?? null
                                                    ),
                                                    'attr'
                                                ) ?>">

                                    <?= esc(
                                        $formatDateTime(
                                            $membership['expiresAt']
                                                ?? null
                                        )
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

<!-- Full Profile usage -->
<h3 class="fs-16 fw-semibold mb-1">
    Full Profile Usage
</h3>

<p class="text-muted fs-13 mb-3">
    A profile consumes membership allowance only on its first
    successful opening during that membership. Repeat openings
    are shown below but do not consume another allowance.
</p>

<?php if ($profileUsage === []): ?>

    <div
        class="border
            rounded
            text-center
            text-muted
            py-4
            mb-4">

        <i
            class="ri-user-search-line
                fs-24
                d-block
                mb-2"
            aria-hidden="true">
        </i>

        No Full Profile membership usage is available.

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

                    <th
                        scope="col"
                        class="text-end">

                        Opens
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
                                                $isoDateTime(
                                                    $usage['firstViewedAt']
                                                        ?? null
                                                ),
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $formatDateTime(
                                        $usage['firstViewedAt']
                                            ?? null
                                    )
                                ) ?>

                            </time>

                        </td>

                        <td class="text-nowrap">

                            <time
                                datetime="<?= esc(
                                                $isoDateTime(
                                                    $usage['lastViewedAt']
                                                        ?? null
                                                ),
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $formatDateTime(
                                        $usage['lastViewedAt']
                                            ?? null
                                    )
                                ) ?>

                            </time>

                        </td>

                        <td class="text-end fw-medium">
                            <?= esc(
                                (string) (
                                    $usage['viewCount']
                                    ?? 1
                                )
                            ) ?>
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
    Each approved Live Introduction version consumes allowance
    only once during the membership. Replays do not consume
    another allowance.
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

                    <th
                        scope="col"
                        class="text-end">

                        Plays
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
                                                $isoDateTime(
                                                    $usage['firstViewedAt']
                                                        ?? null
                                                ),
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $formatDateTime(
                                        $usage['firstViewedAt']
                                            ?? null
                                    )
                                ) ?>

                            </time>

                        </td>

                        <td class="text-nowrap">

                            <time
                                datetime="<?= esc(
                                                $isoDateTime(
                                                    $usage['lastViewedAt']
                                                        ?? null
                                                ),
                                                'attr'
                                            ) ?>">

                                <?= esc(
                                    $formatDateTime(
                                        $usage['lastViewedAt']
                                            ?? null
                                    )
                                ) ?>

                            </time>

                        </td>

                        <td class="text-end fw-medium">
                            <?= esc(
                                (string) (
                                    $usage['viewCount']
                                    ?? 1
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>