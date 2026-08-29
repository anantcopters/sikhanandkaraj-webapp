<?php

declare(strict_types=1);

use CodeIgniter\Pager\Pager;

/**
 * @var array<string, mixed>       $member
 * @var list<array<string,mixed>> $profiles
 * @var Pager                      $pager
 * @var string                     $pagerGroup
 * @var int                        $perPage
 * @var int                        $total
 * @var string                     $search
 * @var string                     $sort
 * @var int                        $minimumMatchPercentage
 */

$member =
    isset($member)
    && is_array($member)
    ? $member
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

$resolvedSearch = trim(
    (string) (
        $search
        ?? ''
    )
);

$resolvedSort = trim(
    (string) (
        $sort
        ?? 'match_score'
    )
);

$resolvedPerPage = max(
    1,
    (int) (
        $perPage
        ?? 9
    )
);

$resolvedPagerGroup = trim(
    (string) (
        $pagerGroup
        ?? 'adminMemberMatches'
    )
);

$comparison =
    isset($matchScoreComparison)
    && is_array(
        $matchScoreComparison
    )
    ? $matchScoreComparison
    : [];

$diagnosticErrors =
    isset($matchScoreDiagnosticErrors)
    && is_array(
        $matchScoreDiagnosticErrors
    )
    ? $matchScoreDiagnosticErrors
    : [];

$diagnosticInput =
    isset($matchScoreDiagnosticInput)
    && is_array(
        $matchScoreDiagnosticInput
    )
    ? $matchScoreDiagnosticInput
    : [];

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
                    justify-content-between
                    gap-3">

                <div>

                    <h4 class="mb-sm-0">
                        Member Matches
                    </h4>

                    <p class="text-muted mb-0 mt-1">

                        Matches for

                        <span class="fw-semibold">
                            <?= esc(
                                $memberName
                            ) ?>
                        </span>

                        <?php if (
                            $memberReference !== ''
                        ): ?>

                            (<?= esc(
                                    $memberReference
                                ) ?>)

                        <?php endif; ?>

                    </p>

                </div>

                <div
                    class="page-title-right
                        mt-3
                        mt-sm-0">

                    <a
                        href="<?= route_to(
                                    'admin.members.index'
                                ) ?>"
                        class="btn
                            btn-light
                            d-inline-flex
                            align-items-center
                            gap-1">

                        <i
                            class="ri-arrow-left-line"
                            aria-hidden="true"></i>

                        Back to Members

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
                action="<?= route_to(
                            'admin.members.matches',
                            $memberId
                        ) ?>"
                class="row
                    g-2
                    align-items-end">

                <div
                    class="col-12
                        col-lg-6">

                    <label
                        for="admin-match-search"
                        class="form-label">

                        Search profile

                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i
                                class="ri-search-line"
                                aria-hidden="true"></i>

                        </span>

                        <input
                            type="search"
                            id="admin-match-search"
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
                        col-md-6
                        col-lg-4">

                    <label
                        for="admin-match-sort"
                        class="form-label">

                        Order profiles

                    </label>

                    <select
                        id="admin-match-sort"
                        name="sort"
                        class="form-select"
                        data-admin-match-sort
                        data-choice
                        data-choice-search="false"
                        data-choice-position="bottom">

                        <option
                            value="match_score"
                            <?= $resolvedSort
                                === 'match_score'
                                ? 'selected'
                                : '' ?>>

                            Match Score — Highest First

                        </option>

                        <option
                            value="partner_preference"
                            <?= $resolvedSort
                                === 'partner_preference'
                                ? 'selected'
                                : '' ?>>

                            Partner Preference % — Highest First

                        </option>

                    </select>

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
                            href="<?= route_to(
                                        'admin.members.matches',
                                        $memberId
                                    ) ?>"
                            class="btn btn-light">

                            Reset

                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>

        <div class="card-body">

            <div
                class="d-flex
                    flex-column
                    flex-sm-row
                    align-items-sm-center
                    justify-content-between
                    gap-2
                    mb-3">

                <div class="text-muted fs-13">

                    <i
                        class="ri-information-line me-1"
                        aria-hidden="true"></i>

                    Only profiles meeting the configured
                    Partner Preference threshold of

                    <strong>
                        <?= esc(
                            (string)
                            $minimumMatchPercentage
                        ) ?>%
                    </strong>

                    are included.

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
                        ? 'Match'
                        : 'Matches' ?>

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
                        class="ri-user-search-line
                            fs-32
                            d-block
                            mb-2"
                        aria-hidden="true"></i>

                    <?php if (
                        $resolvedSearch !== ''
                    ): ?>

                        No matching profile was found in this
                        member's qualified Matches.

                    <?php else: ?>

                        This member currently has no profiles
                        meeting the Match criteria.

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <!-- 3 columns x 3 rows on XL screens -->
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

                                    'memberId' =>
                                    $memberId,
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
                'sort',
            ]);
            ?>

            <div class="card-footer py-3">

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
                        'matches',

                        'surroundCount' =>
                        2,
                    ]
                ) ?>

            </div>

        <?php endif; ?>

    </div>

</div>

<?= view(
    'Admin/Members/Partials/MatchComparisonModal',
    [
        'memberId' =>
        $memberId,

        'memberName' =>
        $memberName,

        'memberReference' =>
        $memberReference,

        'comparison' =>
        $comparison,

        'diagnosticErrors' =>
        $diagnosticErrors,

        'diagnosticInput' =>
        $diagnosticInput,
    ]
) ?>

<?php
$this->endSection();
