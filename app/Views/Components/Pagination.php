<?php

declare(strict_types=1);

use CodeIgniter\Pager\Pager;

/**
 * Reusable application pagination component.
 *
 * Required:
 *
 * @var Pager  $pager
 * @var string $group
 *
 * Optional:
 *
 * @var int    $perPage
 * @var string $itemLabel
 * @var int    $surroundCount
 */

$resolvedGroup = trim(
    (string) (
        $group
        ?? 'default'
    )
);

$resolvedPerPage = max(
    1,
    (int) (
        $perPage
        ?? 10
    )
);

$resolvedItemLabel = trim(
    (string) (
        $itemLabel
        ?? 'records'
    )
);

if ($resolvedItemLabel === '') {
    $resolvedItemLabel = 'records';
}

$resolvedSurroundCount = max(
    1,
    min(
        3,
        (int) (
            $surroundCount
            ?? 2
        )
    )
);

if (
    !isset($pager)
    || !$pager instanceof Pager
) {
    return;
}

/*
 * CI4 keeps the current pagination metadata against the pager group.
 */
$details = $pager->getDetails(
    $resolvedGroup
);

$currentPage = max(
    1,
    (int) (
        $details['currentPage']
        ?? 1
    )
);

$pageCount = max(
    1,
    (int) (
        $details['pageCount']
        ?? 1
    )
);

$total = max(
    0,
    (int) (
        $details['total']
        ?? 0
    )
);

$actualPerPage = max(
    1,
    (int) (
        $details['perPage']
        ?? $resolvedPerPage
    )
);

$firstRecord = $total > 0
    ? (($currentPage - 1) * $actualPerPage) + 1
    : 0;

$lastRecord = $total > 0
    ? min(
        $currentPage * $actualPerPage,
        $total
    )
    : 0;

/*
 * Do not render pagination when there are no results.
 */
if ($total <= 0) {
    return;
}

$previousUrl = $currentPage > 1
    ? $pager->getPageURI(
        $currentPage - 1,
        $resolvedGroup
    )
    : null;

$nextUrl = $currentPage < $pageCount
    ? $pager->getPageURI(
        $currentPage + 1,
        $resolvedGroup
    )
    : null;

/*
 * Keep a small number of page buttons around the active page.
 */
$firstVisiblePage = max(
    1,
    $currentPage - $resolvedSurroundCount
);

$lastVisiblePage = min(
    $pageCount,
    $currentPage + $resolvedSurroundCount
);

/*
 * Maintain a reasonably balanced number of visible page buttons near the
 * beginning and end of the page range.
 */
$expectedVisibleCount =
    ($resolvedSurroundCount * 2) + 1;

$currentVisibleCount =
    $lastVisiblePage
    - $firstVisiblePage
    + 1;

if (
    $currentVisibleCount
    < $expectedVisibleCount
) {
    if ($firstVisiblePage === 1) {
        $lastVisiblePage = min(
            $pageCount,
            $firstVisiblePage
                + $expectedVisibleCount
                - 1
        );
    } elseif (
        $lastVisiblePage === $pageCount
    ) {
        $firstVisiblePage = max(
            1,
            $lastVisiblePage
                - $expectedVisibleCount
                + 1
        );
    }
}
?>

<div
    class="d-flex
        flex-column
        flex-sm-row
        align-items-sm-center
        justify-content-between
        gap-3">

    <div class="text-muted fs-13">
        Showing

        <span class="fw-semibold">
            <?= esc(
                (string) $firstRecord
            ) ?>
        </span>

        to

        <span class="fw-semibold">
            <?= esc(
                (string) $lastRecord
            ) ?>
        </span>

        of

        <span class="fw-semibold">
            <?= esc(
                (string) $total
            ) ?>
        </span>

        <?= esc($resolvedItemLabel) ?>
    </div>

    <?php if ($pageCount > 1): ?>
        <nav
            aria-label="<?= esc(
                            ucfirst($resolvedItemLabel)
                                . ' pagination',
                            'attr'
                        ) ?>">

            <ul
                class="pagination
                    pagination-separated
                    pagination-sm
                    mb-0">

                <!-- Previous page -->
                <li
                    class="page-item <?= $previousUrl === null
                                            ? 'disabled'
                                            : '' ?>">

                    <?php if ($previousUrl !== null): ?>
                        <a
                            href="<?= esc(
                                        $previousUrl,
                                        'attr'
                                    ) ?>"
                            class="page-link"
                            aria-label="Previous page">

                            <i
                                class="ri-arrow-left-s-line"
                                aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span
                            class="page-link"
                            aria-hidden="true">

                            <i
                                class="ri-arrow-left-s-line"
                                aria-hidden="true"></i>
                        </span>
                    <?php endif ?>
                </li>

                <!-- First page and opening ellipsis -->
                <?php if ($firstVisiblePage > 1): ?>
                    <li class="page-item">
                        <a
                            href="<?= esc(
                                        $pager->getPageURI(
                                            1,
                                            $resolvedGroup
                                        ),
                                        'attr'
                                    ) ?>"
                            class="page-link">

                            1
                        </a>
                    </li>

                    <?php if (
                        $firstVisiblePage > 2
                    ): ?>
                        <li
                            class="page-item disabled"
                            aria-hidden="true">

                            <span class="page-link">
                                …
                            </span>
                        </li>
                    <?php endif ?>
                <?php endif ?>

                <!-- Visible page range -->
                <?php for (
                    $pageNumber =
                        $firstVisiblePage;
                    $pageNumber <=
                        $lastVisiblePage;
                    $pageNumber++
                ): ?>
                    <li
                        class="page-item <?= $pageNumber
                                                === $currentPage
                                                ? 'active'
                                                : '' ?>"
                        <?= $pageNumber
                            === $currentPage
                            ? 'aria-current="page"'
                            : '' ?>>

                        <?php if (
                            $pageNumber
                            === $currentPage
                        ): ?>
                            <span class="page-link">
                                <?= esc(
                                    (string) $pageNumber
                                ) ?>
                            </span>
                        <?php else: ?>
                            <a
                                href="<?= esc(
                                            $pager->getPageURI(
                                                $pageNumber,
                                                $resolvedGroup
                                            ),
                                            'attr'
                                        ) ?>"
                                class="page-link">

                                <?= esc(
                                    (string) $pageNumber
                                ) ?>
                            </a>
                        <?php endif ?>
                    </li>
                <?php endfor ?>

                <!-- Closing ellipsis and last page -->
                <?php if (
                    $lastVisiblePage
                    < $pageCount
                ): ?>
                    <?php if (
                        $lastVisiblePage
                        < $pageCount - 1
                    ): ?>
                        <li
                            class="page-item disabled"
                            aria-hidden="true">

                            <span class="page-link">
                                …
                            </span>
                        </li>
                    <?php endif ?>

                    <li class="page-item">
                        <a
                            href="<?= esc(
                                        $pager->getPageURI(
                                            $pageCount,
                                            $resolvedGroup
                                        ),
                                        'attr'
                                    ) ?>"
                            class="page-link">

                            <?= esc(
                                (string) $pageCount
                            ) ?>
                        </a>
                    </li>
                <?php endif ?>

                <!-- Next page -->
                <li
                    class="page-item <?= $nextUrl === null
                                            ? 'disabled'
                                            : '' ?>">

                    <?php if ($nextUrl !== null): ?>
                        <a
                            href="<?= esc(
                                        $nextUrl,
                                        'attr'
                                    ) ?>"
                            class="page-link"
                            aria-label="Next page">

                            <i
                                class="ri-arrow-right-s-line"
                                aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span
                            class="page-link"
                            aria-hidden="true">

                            <i
                                class="ri-arrow-right-s-line"
                                aria-hidden="true"></i>
                        </span>
                    <?php endif ?>
                </li>
            </ul>
        </nav>
    <?php endif ?>
</div>