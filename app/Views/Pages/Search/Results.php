<?php

declare(strict_types=1);

/**
 * Search-results UI variables.
 *
 * @var string                     $pageTitle
 * @var string                     $mode
 * @var string                     $sort
 * @var int                        $page
 * @var int                        $perPage
 * @var int                        $total
 * @var int                        $totalPages
 * @var list<array<string, mixed>> $profiles
 * @var list<string>               $searchChips
 * @var array<string, mixed>       $filters
 * @var string                     $backToSearchUrl
 * @var array<string, string>|null $formAlert
 */

/*
 * --------------------------------------------------------------------------
 * Normalize view-local values
 * --------------------------------------------------------------------------
 */

$pageTitle =
    isset($pageTitle)
    && is_string($pageTitle)
    ? trim($pageTitle)
    : 'Search Results';

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

$total =
    isset($total)
    && is_numeric($total)
    ? max(
        0,
        (int) $total
    )
    : 0;

$totalPages =
    isset($totalPages)
    && is_numeric($totalPages)
    ? max(
        1,
        (int) $totalPages
    )
    : 1;

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

/*
 * Build result URL parameters only from normalized Search state.
 */
$resultQuery =
    $filters;

$resultQuery['mode'] =
    $mode;

$resultQuery['sort'] =
    $sort;

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);
?>

<section class="py-3 py-lg-4">
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
                flex-md-row align-items-md-center
                justify-content-between gap-3 mb-3">

            <div>
                <a
                    href="<?= esc(
                                $backToSearchUrl,
                                'attr'
                            ) ?>"
                    class="d-inline-flex
                        align-items-center gap-1
                        text-primary fw-medium mb-2">

                    <i
                        class="ri-arrow-left-line"
                        aria-hidden="true">
                    </i>

                    Back to Search
                </a>

                <h1
                    class="fs-24 fw-semibold mb-1">

                    Search Results
                </h1>

                <p class="text-success fw-medium fs-14 mb-0">
                    <?= esc(
                        (string) $total
                    ) ?>
                    matching profiles
                </p>
            </div>

            <!-- =========================================================
                 Choices sorting
                 ========================================================= -->

            <form
                action="<?= url_to(
                            'web.search.results'
                        ) ?>"
                method="get"
                class="d-flex align-items-center gap-2">

                <?php foreach (
                    $filters
                    as $key => $value
                ): ?>

                    <?php if (
                        $key === 'sort'
                        || $key === 'page'
                    ) {
                        continue;
                    } ?>

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
                                            (string)
                                            $item,
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
                                        (string)
                                        $value,
                                        'attr'
                                    ) ?>">

                    <?php endif; ?>

                <?php endforeach; ?>

                <select
                    name="sort"
                    class="form-select"
                    data-choice
                    data-choice-search="false"
                    aria-label="Sort Search results"
                    onchange="this.form.submit()">

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
             Active Search criteria chips
             ============================================================= -->

        <?php if (
            $searchChips !== []
        ): ?>

            <div
                class="d-flex flex-wrap
                    align-items-center gap-2 mb-3">

                <span
                    class="text-muted fs-13 fw-medium">

                    Your search:
                </span>

                <?php foreach (
                    $searchChips
                    as $chip
                ): ?>

                    <span
                        class="badge rounded-pill
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
                    class="btn btn-outline-danger
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
             Result / Quick Search layout
             ============================================================= -->

        <div class="row g-4">

            <!-- Future Quick Search panel -->

            <aside class="col-12 col-lg-3">

                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-4">

                        <div class="avatar-sm mb-3">
                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-flashlight-line fs-20"
                                    aria-hidden="true">
                                </i>

                            </span>
                        </div>

                        <h2
                            class="fs-16 fw-semibold mb-2">

                            Quick Search
                        </h2>

                        <p
                            class="text-muted fs-13 mb-0">

                            Quick search options
                            will be available here soon.
                        </p>

                    </div>
                </div>

            </aside>

            <!-- Profile results -->

            <div class="col-12 col-lg-9">

                <?php if (
                    $profiles === []
                ): ?>

                    <!-- Empty Search result -->

                    <div
                        class="card border border-danger
                            border-opacity-25 shadow-sm">

                        <div
                            class="card-body p-5 text-center">

                            <i
                                class="ri-search-eye-line
                                    fs-36 text-muted"
                                aria-hidden="true">
                            </i>

                            <h2
                                class="fs-18 fw-semibold mt-3 mb-2">

                                No profiles found
                            </h2>

                            <p
                                class="text-muted mb-3">

                                Try widening one or more
                                search preferences.
                            </p>

                            <a
                                href="<?= esc(
                                            $backToSearchUrl,
                                            'attr'
                                        ) ?>"
                                class="btn btn-outline-primary">

                                Modify Search
                            </a>

                        </div>
                    </div>

                <?php else: ?>

                    <!-- Two profiles per desktop row -->

                    <div class="row g-3">

                        <?php foreach (
                            $profiles
                            as $profile
                        ): ?>

                            <div
                                class="col-12 col-xl-6">

                                <?= view(
                                    'Pages/Search/_profile_card',
                                    [
                                        'profile' =>
                                        $profile,
                                    ]
                                ) ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <!-- =================================================
                         Pagination
                         ================================================= -->

                    <?php if (
                        $totalPages > 1
                    ): ?>

                        <nav
                            class="mt-4"
                            aria-label="Search result pages">

                            <ul
                                class="pagination
                                    justify-content-center
                                    flex-wrap mb-0">

                                <?php for (
                                    $pageNumber = 1;
                                    $pageNumber <= $totalPages;
                                    ++$pageNumber
                                ): ?>

                                    <?php
                                    $pageQuery =
                                        $resultQuery;

                                    $pageQuery['page'] =
                                        $pageNumber;

                                    $pageUrl =
                                        route_to(
                                            'web.search.results'
                                        )
                                        . '?'
                                        . http_build_query(
                                            $pageQuery
                                        );
                                    ?>

                                    <li
                                        class="page-item
                                            <?= $pageNumber === $page
                                                ? 'active'
                                                : '' ?>">

                                        <a
                                            href="<?= esc(
                                                        $pageUrl,
                                                        'attr'
                                                    ) ?>"
                                            class="page-link">

                                            <?= esc(
                                                (string)
                                                $pageNumber
                                            ) ?>

                                        </a>

                                    </li>

                                <?php endfor; ?>

                            </ul>

                        </nav>

                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>

    </div>
</section>

<?php $this->endSection(); ?>