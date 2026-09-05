<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $verifiedProfileUsage
 */

$usageData =
    isset($verifiedProfileUsage)
    && is_array($verifiedProfileUsage)
    ? $verifiedProfileUsage
    : [];

$rows =
    isset($usageData['rows'])
    && is_array($usageData['rows'])
    ? $usageData['rows']
    : [];

$search =
    trim(
        (string) (
            $usageData['search']
            ?? ''
        )
    );

$page =
    max(
        1,
        (int) (
            $usageData['page']
            ?? 1
        )
    );

$totalPages =
    max(
        1,
        (int) (
            $usageData['totalPages']
            ?? 1
        )
    );

$total =
    max(
        0,
        (int) (
            $usageData['total']
            ?? 0
        )
    );

$sectionUrl =
    route_to(
        'web.account.settings.section',
        'verified-profile-usage'
    );
?>

<div
    class="
        d-flex
        align-items-center
        gap-2
        mb-1
    ">

    <span class="avatar-sm flex-shrink-0">

        <span
            class="
                avatar-title
                rounded-circle
                bg-success-subtle
                text-success
            ">

            <i
                class="ri-user-search-line fs-20"
                aria-hidden="true">
            </i>

        </span>

    </span>

    <div>

        <h2 class="fs-18 fw-semibold mb-0">
            Verified Profile Usage
        </h2>

        <p class="text-muted fs-13 mb-0">
            Review Verified Profiles consumed through your membership.
        </p>

    </div>

</div>

<hr class="my-4">

<p class="text-muted fs-13 mb-3">
    A Verified Profile consumes membership allowance only on its
    first successful opening during that membership. Repeat openings
    do not consume another allowance.
</p>

<form
    method="get"
    action="<?= esc(
                $sectionUrl,
                'attr'
            ) ?>"
    class="row g-2 align-items-end mb-4">

    <div class="col-12 col-md">

        <label
            for="verifiedProfileUsageSearch"
            class="form-label fs-13">

            Search
        </label>

        <input
            type="search"
            class="form-control"
            id="verifiedProfileUsageSearch"
            name="q"
            value="<?= esc(
                        $search,
                        'attr'
                    ) ?>"
            maxlength="100"
            placeholder="Search by Profile ID or membership">

    </div>

    <div class="col-12 col-md-auto">

        <button
            type="submit"
            class="btn btn-primary">

            <i
                class="ri-search-line me-1"
                aria-hidden="true">
            </i>

            Search

        </button>

        <?php if ($search !== ''): ?>

            <a
                href="<?= esc(
                            $sectionUrl,
                            'attr'
                        ) ?>"
                class="btn btn-outline-secondary">

                Reset

            </a>

        <?php endif; ?>

    </div>

</form>

<?php if ($rows === []): ?>

    <div
        class="
            border
            rounded
            text-center
            text-muted
            py-4
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

        <?php if ($search !== ''): ?>

            No Verified Profile usage matched your search.

        <?php else: ?>

            No Verified Profile membership usage is available.

        <?php endif; ?>

    </div>

<?php else: ?>

    <div
        class="
            d-flex
            justify-content-between
            align-items-center
            flex-wrap
            gap-2
            mb-2
        ">

        <div class="text-muted fs-13">

            <?= esc(
                (string) $total
            ) ?>

            usage
            <?= $total === 1
                ? 'record'
                : 'records' ?>

        </div>

    </div>

    <div class="table-responsive">

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

                    <th scope="col">
                        Views
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php foreach (
                    $rows
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

                        <td>

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

<?php if ($totalPages > 1): ?>

    <nav
        class="mt-4"
        aria-label="Verified Profile usage pages">

        <ul
            class="
                pagination
                pagination-sm
                justify-content-end
                mb-0
            ">

            <li
                class="page-item <?= $page <= 1
                                        ? 'disabled'
                                        : '' ?>">

                <a
                    class="page-link"
                    href="<?= esc(
                                $sectionUrl
                                    . '?'
                                    . http_build_query(
                                        array_filter(
                                            [
                                                'q' =>
                                                $search,

                                                'page' =>
                                                max(
                                                    1,
                                                    $page - 1
                                                ),
                                            ],
                                            static fn(
                                                mixed $value
                                            ): bool =>
                                            $value !== ''
                                        )
                                    ),
                                'attr'
                            ) ?>">

                    Previous

                </a>

            </li>

            <?php for (
                $pageNumber = 1;
                $pageNumber <= $totalPages;
                $pageNumber++
            ): ?>

                <li
                    class="page-item <?= $pageNumber === $page
                                            ? 'active'
                                            : '' ?>">

                    <a
                        class="page-link"
                        href="<?= esc(
                                    $sectionUrl
                                        . '?'
                                        . http_build_query(
                                            array_filter(
                                                [
                                                    'q' =>
                                                    $search,

                                                    'page' =>
                                                    $pageNumber,
                                                ],
                                                static fn(
                                                    mixed $value
                                                ): bool =>
                                                $value !== ''
                                            )
                                        ),
                                    'attr'
                                ) ?>">

                        <?= esc(
                            (string) $pageNumber
                        ) ?>

                    </a>

                </li>

            <?php endfor; ?>

            <li
                class="page-item <?= $page >= $totalPages
                                        ? 'disabled'
                                        : '' ?>">

                <a
                    class="page-link"
                    href="<?= esc(
                                $sectionUrl
                                    . '?'
                                    . http_build_query(
                                        array_filter(
                                            [
                                                'q' =>
                                                $search,

                                                'page' =>
                                                min(
                                                    $totalPages,
                                                    $page + 1
                                                ),
                                            ],
                                            static fn(
                                                mixed $value
                                            ): bool =>
                                            $value !== ''
                                        )
                                    ),
                                'attr'
                            ) ?>">

                    Next

                </a>

            </li>

        </ul>

    </nav>

<?php endif; ?>