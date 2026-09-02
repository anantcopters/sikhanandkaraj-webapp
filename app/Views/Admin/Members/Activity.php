<?php

declare(strict_types=1);

use CodeIgniter\Pager\Pager;

/**
 * @var array<string, mixed>       $member
 * @var array<string, string>      $activity
 * @var list<array<string,mixed>> $profiles
 * @var Pager                      $pager
 * @var string                     $pagerGroup
 * @var int                        $perPage
 * @var int                        $total
 * @var string                     $search
 */

$member =
    isset($member)
    && is_array($member)
    ? $member
    : [];

$activity =
    isset($activity)
    && is_array($activity)
    ? $activity
    : [];

$profiles =
    isset($profiles)
    && is_array($profiles)
    ? $profiles
    : [];

$memberId = max(
    0,
    (int) (
        $member['id']
        ?? 0
    )
);

$memberName = trim(
    (string) (
        $member['full_name']
        ?? 'Member'
    )
);

$memberReference = trim(
    (string) (
        $member['profile_ref_number']
        ?? ''
    )
);

$activityLabel = trim(
    (string) (
        $activity['label']
        ?? 'Member Activity'
    )
);

$activityDescription = trim(
    (string) (
        $activity['description']
        ?? ''
    )
);

$activityIcon = trim(
    (string) (
        $activity['icon']
        ?? 'ri-line-chart-line'
    )
);

$resolvedSearch = trim(
    (string) (
        $search
        ?? ''
    )
);

$resolvedPagerGroup = trim(
    (string) (
        $pagerGroup
        ?? 'adminMemberActivity'
    )
);

$resolvedPerPage = max(
    1,
    (int) (
        $perPage
        ?? 9
    )
);

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">

    <div class="row">

        <div class="col-12">

            <div
                class="page-title-box
                    d-sm-flex
                    align-items-sm-center
                    justify-content-between">

                <div>

                    <h4 class="mb-sm-0">

                        <i
                            class="<?= esc(
                                        $activityIcon,
                                        'attr'
                                    ) ?> me-1"
                            aria-hidden="true"></i>

                        <?= esc(
                            $activityLabel
                        ) ?>

                    </h4>

                    <p
                        class="text-muted
                            mb-0
                            mt-1">

                        <?= esc(
                            $memberName
                        ) ?>

                        <?php if (
                            $memberReference !== ''
                        ): ?>

                            (<?= esc(
                                    $memberReference
                                ) ?>)

                        <?php endif; ?>

                        <?php if (
                            $activityDescription !== ''
                        ): ?>

                            ·
                            <?= esc(
                                $activityDescription
                            ) ?>

                        <?php endif; ?>

                    </p>

                </div>

                <div
                    class="page-title-right
                        mt-3
                        mt-sm-0">

                    <a
                        href="<?= route_to(
                                    'admin.members.view',
                                    $memberId
                                ) ?>"
                        class="btn
                            btn-light
                            d-inline-flex
                            align-items-center
                            gap-1"
                        data-admin-match-navigation>

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true"></i>

                        Back to Member

                    </a>

                </div>

            </div>

        </div>

    </div>

    <div
        class="card
            border
            border-danger
            border-opacity-25">

        <div class="card-header">

            <form
                method="get"
                class="row
                    g-2
                    align-items-end"
                data-admin-match-form>

                <div
                    class="col-12
                        col-lg-7">

                    <label
                        for="admin-activity-search"
                        class="form-label">

                        Search profile

                    </label>

                    <div class="input-group">

                        <span
                            class="input-group-text">

                            <i
                                class="ri-search-line"
                                aria-hidden="true"></i>

                        </span>

                        <input
                            type="search"
                            id="admin-activity-search"
                            name="search"
                            class="form-control"
                            maxlength="100"
                            value="<?= esc(
                                        $resolvedSearch,
                                        'attr'
                                    ) ?>"
                            placeholder="Profile ID or member name">

                    </div>

                </div>

                <div
                    class="col-12
                        col-lg-auto">

                    <button
                        type="submit"
                        class="btn
                            btn-primary">

                        <i
                            class="ri-search-line me-1"
                            aria-hidden="true"></i>

                        Search

                    </button>

                    <?php if (
                        $resolvedSearch !== ''
                    ): ?>

                        <a
                            href="<?= current_url() ?>"
                            class="btn
                                btn-light"
                            data-admin-match-navigation>

                            Reset

                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>

        <div class="card-body">

            <div
                class="d-flex
                    align-items-center
                    justify-content-between
                    gap-2
                    mb-3">

                <div
                    class="text-muted
                        fs-13">

                    <?= esc(
                        $activityDescription
                    ) ?>

                </div>

                <span
                    class="badge
                        bg-primary-subtle
                        text-black
                        p-2">

                    <?= esc(
                        (string) $total
                    ) ?>

                    <?= $total === 1
                        ? 'Profile'
                        : 'Profiles' ?>

                </span>

            </div>

            <?php if (
                $profiles === []
            ): ?>

                <div
                    class="text-center
                        text-muted
                        py-5">

                    <i
                        class="<?= esc(
                                    $activityIcon,
                                    'attr'
                                ) ?>
                            fs-32
                            d-block
                            mb-2"
                        aria-hidden="true"></i>

                    <?php if (
                        $resolvedSearch !== ''
                    ): ?>

                        No profile matching your search
                        was found in this activity.

                    <?php else: ?>

                        No profiles are available for
                        this activity.

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <div class="row g-3">

                    <?php foreach (
                        $profiles
                        as $profile
                    ): ?>

                        <div
                            class="col-12
                                col-md-6
                                col-xl-4">

                            <?= view(
                                'Admin/Members/Partials/MatchCard',
                                [
                                    'profile' =>
                                    $profile,
                                ]
                            ) ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <?php if (
            $profiles !== []
        ): ?>

            <?php
            $pager->only([
                'search',
            ]);
            ?>

            <div
                class="card-footer py-3"
                data-admin-match-pagination>

                <?= view(
                    'Components/Pagination',
                    [
                        'pager' =>
                        $pager,

                        'group' =>
                        $resolvedPagerGroup,

                        'perPage' =>
                        $resolvedPerPage,

                        'itemLabel' =>
                        'profiles',

                        'surroundCount' =>
                        2,
                    ]
                ) ?>

            </div>

        <?php endif; ?>

    </div>

    <!--
        Reuse the exact Admin Match page loading state.
        admin-member-matches.js already knows these data attributes.
    -->
    <div
        class="position-fixed
            top-0
            start-0
            w-100
            h-100
            bg-light
            bg-opacity-75
            d-none
            align-items-center
            justify-content-center"
        style="z-index: 2000;"
        data-admin-match-results-loader
        aria-hidden="true">

        <div class="text-center">

            <div
                class="spinner-border
                    text-primary
                    mb-3"
                role="status">

                <span class="visually-hidden">
                    Loading profiles...
                </span>

            </div>

            <div class="fw-semibold">
                Loading profiles...
            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>