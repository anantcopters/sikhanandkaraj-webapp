<?php

declare(strict_types=1);

use CodeIgniter\Pager\Pager;

/**
 * Search-results UI variables.
 *
 * @var string                     $pageTitle
 * @var string                     $mode
 * @var string                     $sort
 * @var int                        $page
 * @var int                        $perPage
 * @var int                        $total
 * @var list<array<string, mixed>> $profiles
 * @var list<string>               $searchChips
 * @var array<string, mixed>       $filters
 * @var list<array<string, mixed>> $quickLinkGroups
 * @var string                     $backToSearchUrl
 * @var array<string, string>|null $formAlert
 * @var Pager                      $pager
 * @var string                     $pagerGroup
 * @var string                     $resultTitle
 * @var bool                       $showBackToSearch
 * @var bool                       $showSearchCriteria
 */

/*
 * --------------------------------------------------------------------------
 * Normalize view-local variables
 * --------------------------------------------------------------------------
 */

$pageTitle =
    isset($pageTitle)
    && is_string($pageTitle)
    && trim($pageTitle) !== ''
    ? trim($pageTitle)
    : 'Search Results';

/*
 * --------------------------------------------------------------------------
 * Result presentation context
 * --------------------------------------------------------------------------
 */

$resultTitle =
    isset($resultTitle)
    && is_string($resultTitle)
    && trim($resultTitle) !== ''
    ? trim(
        $resultTitle
    )
    : 'Search Results';

$showBackToSearch =
    isset($showBackToSearch)
    ? (bool) $showBackToSearch
    : true;

$showSearchCriteria =
    isset($showSearchCriteria)
    ? (bool) $showSearchCriteria
    : true;

$mode =
    isset($mode)
    && $mode === 'advanced'
    ? 'advanced'
    : 'basic';

$sort =
    isset($sort)
    && is_string($sort)
    ? trim($sort)
    : 'default';

$page =
    isset($page)
    && is_numeric($page)
    ? max(
        1,
        (int) $page
    )
    : 1;

$perPage =
    isset($perPage)
    && is_numeric($perPage)
    ? max(
        1,
        (int) $perPage
    )
    : 10;

$total =
    isset($total)
    && is_numeric($total)
    ? max(
        0,
        (int) $total
    )
    : 0;

$profiles =
    isset($profiles)
    && is_array($profiles)
    ? array_values(
        $profiles
    )
    : [];

$searchChips =
    isset($searchChips)
    && is_array($searchChips)
    ? array_values(
        array_filter(
            $searchChips,
            'is_string'
        )
    )
    : [];

$filters =
    isset($filters)
    && is_array($filters)
    ? $filters
    : [];

$quickLinkGroups =
    isset($quickLinkGroups)
    && is_array($quickLinkGroups)
    ? array_values(
        $quickLinkGroups
    )
    : [];

$backToSearchUrl =
    isset($backToSearchUrl)
    && is_string($backToSearchUrl)
    && trim($backToSearchUrl) !== ''
    ? trim($backToSearchUrl)
    : route_to(
        'web.search'
    );

$formAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$pagerGroup =
    isset($pagerGroup)
    && is_string($pagerGroup)
    && trim($pagerGroup) !== ''
    ? trim($pagerGroup)
    : 'default';

/*
 * Sorting preserves the current normalized criteria.
 */
$sortingFilters =
    $filters;

unset(
    $sortingFilters['sort'],
    $sortingFilters['page']
);

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);
?>

<section class="py-3 py-lg-3">
    <div
        class="
        position-fixed
        top-0
        start-0
        w-100
        h-100
        bg-light
        bg-opacity-75
        d-none
        align-items-center
        justify-content-center
    "
        style="z-index: 2000;"
        data-search-results-loader
        aria-hidden="true">

        <div class="text-center">

            <div
                class="
                spinner-border
                text-primary
                mb-3
            "
                role="status">

                <span class="visually-hidden">
                    Loading matches...
                </span>

            </div>

            <div class="fw-medium">
                Loading profiles...
            </div>

        </div>

    </div>
    <div class="container">

        <!-- =============================================================
             Feedback
             ============================================================= -->

        <?= view(
            'Pages/Profile/Partials/_feedback_alert',
            [
                'formAlert' =>
                $formAlert,
            ]
        ) ?>

        <!-- =============================================================
             Result heading / Back to Search
             ============================================================= -->

        <div
            class="d-flex flex-column
                flex-md-row
                align-items-md-center
                justify-content-between
                gap-3 mb-3">

            <div>

                <?php if (
                    $showBackToSearch
                ): ?>

                    <!--
        Search-only navigation.
        Matches originates from the main member menu and does not need this.
    -->
                    <a
                        href="<?= esc(
                                    $backToSearchUrl,
                                    'attr'
                                ) ?>"
                        class="d-inline-flex
            align-items-center
            gap-1
            text-primary
            fw-medium mb-2">

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true">
                        </i>

                        Back to Search

                    </a>

                <?php endif; ?>

                <h1
                    class="fs-24 fw-semibold mb-1">

                    <?= esc(
                        $resultTitle
                    ) ?>
                </h1>

                <p
                    class="text-success
                        fw-medium fs-14 mb-0">

                    <?= esc(
                        (string) $total
                    ) ?>
                    matching profiles

                </p>

            </div>

            <!-- =========================================================
                 Sort
                 ========================================================= -->

            <form
                action="<?= url_to(
                            'web.search.results'
                        ) ?>"
                method="get"
                class="d-flex
                    align-items-center gap-2"
                data-search-result-sort-form>

                <?php foreach (
                    $sortingFilters
                    as $key => $value
                ): ?>

                    <?php if (
                        is_array($value)
                    ): ?>

                        <?php foreach (
                            $value
                            as $item
                        ): ?>

                            <input
                                type="hidden"
                                name="<?= esc(
                                            $key . '[]',
                                            'attr'
                                        ) ?>"
                                value="<?= esc(
                                            (string) $item,
                                            'attr'
                                        ) ?>">

                        <?php endforeach; ?>

                    <?php elseif (
                        trim(
                            (string) $value
                        ) !== ''
                    ): ?>

                        <input
                            type="hidden"
                            name="<?= esc(
                                        $key,
                                        'attr'
                                    ) ?>"
                            value="<?= esc(
                                        (string) $value,
                                        'attr'
                                    ) ?>">

                    <?php endif; ?>

                <?php endforeach; ?>

                <select
                    name="sort"
                    class="form-select"
                    data-choice
                    data-choice-search="false"
                    aria-label="Sort Search results">

                    <option
                        value="default"
                        <?= $sort === 'default'
                            ? 'selected'
                            : '' ?>>

                        Default Order
                    </option>

                    <option
                        value="latest"
                        <?= $sort === 'latest'
                            ? 'selected'
                            : '' ?>>

                        Latest First
                    </option>

                    <option
                        value="oldest"
                        <?= $sort === 'oldest'
                            ? 'selected'
                            : '' ?>>

                        Oldest First
                    </option>

                    <option
                        value="last_login"
                        <?= $sort === 'last_login'
                            ? 'selected'
                            : '' ?>>

                        Recently Active
                    </option>

                </select>

            </form>

        </div>

        <!-- =============================================================
             Active Search chips
             ============================================================= -->

        <?php if (
            $showSearchCriteria
            && $searchChips !== []
        ): ?>

            <div
                class="d-flex flex-wrap
                    align-items-center
                    gap-2 mb-3">

                <span
                    class="text-muted
                        fs-13 fw-medium">

                    Your search:
                </span>

                <?php foreach (
                    $searchChips
                    as $chip
                ): ?>

                    <span
                        class="badge
                            rounded-pill
                            bg-primary-subtle
                            text-primary p-2">

                        <?= esc(
                            $chip
                        ) ?>

                    </span>

                <?php endforeach; ?>

                <a
                    href="<?= esc(
                                $backToSearchUrl,
                                'attr'
                            ) ?>"
                    class="btn
                        btn-outline-danger
                        d-inline-flex
                        align-items-center
                        justify-content-center
                        gap-1 py-1 px-3">

                    <i
                        class="ri-edit-line"
                        aria-hidden="true">
                    </i>

                    Modify

                </a>

            </div>

        <?php endif; ?>

        <!-- =============================================================
             Quick Links / Search Results
             ============================================================= -->

        <div class="row g-4">

            <!-- =========================================================
                 Quick Links
                 ========================================================= -->

            <aside class="col-12 col-lg-3">

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-4">

                        <div
                            class="d-flex
                                align-items-center
                                gap-2 mb-4">

                            <span
                                class="avatar-sm flex-shrink-0">

                                <span
                                    class="avatar-title
                                        rounded-circle
                                        bg-primary-subtle
                                        text-primary">

                                    <i
                                        class="ri-flashlight-line
                                            fs-20"
                                        aria-hidden="true">
                                    </i>

                                </span>

                            </span>

                            <div>

                                <h2
                                    class="fs-16
                                        fw-semibold mb-1">

                                    Quick Links
                                </h2>

                                <p
                                    class="text-muted
                                        fs-12 mb-0">

                                    Explore profiles using
                                    your activity and profile.
                                </p>

                            </div>

                        </div>

                        <?php foreach (
                            $quickLinkGroups
                            as $groupIndex => $group
                        ): ?>

                            <?php
                            if (!is_array($group)) {
                                continue;
                            }

                            $groupTitle =
                                trim(
                                    (string) (
                                        $group['title']
                                        ?? ''
                                    )
                                );

                            $groupItems =
                                isset(
                                    $group['items']
                                )
                                && is_array(
                                    $group['items']
                                )
                                ? array_values(
                                    $group['items']
                                )
                                : [];

                            if (
                                $groupTitle === ''
                                || $groupItems === []
                            ) {
                                continue;
                            }
                            ?>

                            <?php if (
                                $groupIndex > 0
                            ): ?>

                                <hr class="my-4">

                            <?php endif; ?>

                            <h3
                                class="fs-13
                                    fw-semibold mb-3">

                                <?= esc(
                                    $groupTitle
                                ) ?>

                            </h3>

                            <div
                                class="d-flex
                                    flex-column gap-3">

                                <?php foreach (
                                    $groupItems
                                    as $item
                                ): ?>

                                    <?php
                                    if (!is_array($item)) {
                                        continue;
                                    }

                                    $label =
                                        trim(
                                            (string) (
                                                $item['label']
                                                ?? ''
                                            )
                                        );

                                    $help =
                                        trim(
                                            (string) (
                                                $item['help']
                                                ?? ''
                                            )
                                        );

                                    $icon =
                                        trim(
                                            (string) (
                                                $item['icon']
                                                ?? 'ri-arrow-right-line'
                                            )
                                        );

                                    $url =
                                        trim(
                                            (string) (
                                                $item['url']
                                                ?? ''
                                            )
                                        );

                                    $available =
                                        (
                                            $item['available']
                                            ?? false
                                        ) === true;
                                    ?>

                                    <?php if (
                                        $available
                                        && $url !== ''
                                    ): ?>

                                        <a
                                            href="<?= esc(
                                                        $url,
                                                        'attr'
                                                    ) ?>"
                                            class="d-flex
                                                align-items-start
                                                gap-2
                                                text-decoration-none">

                                            <i
                                                class="<?= esc(
                                                            $icon,
                                                            'attr'
                                                        ) ?>
                                                    fs-18
                                                    text-primary
                                                    flex-shrink-0"
                                                aria-hidden="true">
                                            </i>

                                            <span>

                                                <span
                                                    class="d-block
                                                        text-body
                                                        fs-13
                                                        fw-medium">

                                                    <?= esc(
                                                        $label
                                                    ) ?>

                                                </span>

                                                <?php if (
                                                    $help !== ''
                                                ): ?>

                                                    <span
                                                        class="d-block
                                                            text-muted
                                                            fs-12">

                                                        <?= esc(
                                                            $help
                                                        ) ?>

                                                    </span>

                                                <?php endif; ?>

                                            </span>

                                        </a>

                                    <?php else: ?>

                                        <div
                                            class="d-flex
                                                align-items-start
                                                gap-2 text-muted">

                                            <i
                                                class="<?= esc(
                                                            $icon,
                                                            'attr'
                                                        ) ?>
                                                    fs-18
                                                    flex-shrink-0"
                                                aria-hidden="true">
                                            </i>

                                            <span>

                                                <span
                                                    class="d-block
                                                        fs-13
                                                        fw-medium">

                                                    <?= esc(
                                                        $label
                                                    ) ?>

                                                </span>

                                                <?php if (
                                                    $help !== ''
                                                ): ?>

                                                    <span
                                                        class="d-block
                                                            fs-12">

                                                        <?= esc(
                                                            $help
                                                        ) ?>

                                                    </span>

                                                <?php endif; ?>

                                            </span>

                                        </div>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>

            </aside>

            <!-- =========================================================
                 Profiles
                 ========================================================= -->

            <div class="col-12 col-lg-9">

                <?php if (
                    $profiles === []
                ): ?>

                    <div
                        class="card border border-danger
                            border-opacity-25 shadow-sm">

                        <div
                            class="card-body
                                p-5 text-center">

                            <i
                                class="ri-search-eye-line
                                    fs-36 text-muted"
                                aria-hidden="true">
                            </i>

                            <h2
                                class="fs-18
        fw-semibold
        mt-3 mb-2">

                                No profiles found

                            </h2>

                            <p class="text-muted mb-3">

                                <?php if (
                                    $showSearchCriteria
                                ): ?>

                                    Try widening one or more
                                    search preferences.

                                <?php else: ?>

                                    No profiles currently meet
                                    your Partner Preferences.

                                <?php endif; ?>

                            </p>

                            <?php if (
                                $showBackToSearch
                            ): ?>

                                <a
                                    href="<?= esc(
                                                $backToSearchUrl,
                                                'attr'
                                            ) ?>"
                                    class="btn btn-outline-primary">

                                    Modify Search

                                </a>

                            <?php endif; ?>

                        </div>
                    </div>

                <?php else: ?>

                    <!-- Two profile cards per XL desktop row. -->

                    <div class="row g-3">

                        <?php foreach (
                            $profiles
                            as $profile
                        ): ?>

                            <div
                                class="col-12 col-xl-6">

                                <?= view(
                                    'Components/Member/ProfileCard',
                                    [
                                        'profile' =>
                                        $profile,
                                    ],
                                    [
                                        'saveData' =>
                                        false,
                                    ]
                                ) ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <!-- =================================================
                         Shared application pagination
                         ================================================= -->

                    <div class="mt-4">

                        <?= view(
                            'Components/Pagination',
                            [
                                'pager' =>
                                $pager,

                                'group' =>
                                $pagerGroup,

                                'perPage' =>
                                $perPage,

                                'itemLabel' =>
                                'profiles',

                                'surroundCount' =>
                                2,
                            ],
                            [
                                'saveData' =>
                                false,
                            ]
                        ) ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>
</section>

<?php $this->endSection(); ?>