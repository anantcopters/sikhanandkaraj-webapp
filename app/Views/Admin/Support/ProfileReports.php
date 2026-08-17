<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>> $reports
 * @var mixed                      $pager
 * @var string                     $selectedStatus
 * @var string                     $searchTerm
 * @var array<string, string>      $validationErrors
 * @var array<string, mixed>       $reviewRecord
 * @var array<string, mixed>|null  $formAlert
 */

$resolvedReports =
    isset($reports)
    && is_array($reports)
    ? $reports
    : [];

$resolvedStatus = in_array(
    $selectedStatus ?? '',
    [
        'ALL',
        'OPEN',
        'REVIEWED',
        'DISMISSED',
        'ACTION_TAKEN',
    ],
    true
)
    ? (string) $selectedStatus
    : 'OPEN';

$resolvedSearch = trim(
    (string) (
        $searchTerm
        ?? ''
    )
);

$resolvedErrors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$resolvedAlert =
    isset($formAlert)
    && is_array($formAlert)
    ? $formAlert
    : null;

$this->extend(
    'Admin/Layouts/Main'
);

$this->section('content');
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
                        Reported Profiles
                    </h4>

                    <p class="text-muted mb-0">
                        Review profile and safety reports
                        submitted by members.
                    </p>
                </div>

                <div class="page-title-right mt-3 mt-sm-0">
                    <form
                        method="get"
                        action="<?= route_to(
                                    'admin.support.reports'
                                ) ?>"
                        class="d-flex gap-2">

                        <?php if (
                            $resolvedSearch !== ''
                        ): ?>
                            <input
                                type="hidden"
                                name="search"
                                value="<?= esc(
                                            $resolvedSearch,
                                            'attr'
                                        ) ?>">
                        <?php endif; ?>

                        <label
                            for="profile-report-status"
                            class="visually-hidden">

                            Filter reports by status
                        </label>

                        <select
                            id="profile-report-status"
                            name="status"
                            class="form-select"
                            data-choice
                            data-choice-search="false"
                            data-choice-position="bottom">

                            <?php foreach (
                                [
                                    'ALL' =>
                                    'All Reports',

                                    'OPEN' =>
                                    'Open',

                                    'REVIEWED' =>
                                    'Reviewed',

                                    'DISMISSED' =>
                                    'Dismissed',

                                    'ACTION_TAKEN' =>
                                    'Action Taken',
                                ] as $value => $label
                            ): ?>
                                <option
                                    value="<?= esc(
                                                $value,
                                                'attr'
                                            ) ?>"
                                    <?= $resolvedStatus
                                        === $value
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button
                            type="submit"
                            class="btn btn-primary">

                            Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $resolvedAlert,
        ]
    ) ?>

    <div
        class="card
            border
            border-danger
            border-opacity-25">

        <div class="card-header">
            <form
                method="get"
                action="<?= route_to(
                            'admin.support.reports'
                        ) ?>"
                class="row g-2 align-items-end">

                <input
                    type="hidden"
                    name="status"
                    value="<?= esc(
                                $resolvedStatus,
                                'attr'
                            ) ?>">

                <div class="col-12 col-md-8 col-lg-6">
                    <label
                        for="profile-report-search"
                        class="form-label">

                        Search reports
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i
                                class="ri-search-line"
                                aria-hidden="true">
                            </i>
                        </span>

                        <input
                            type="search"
                            id="profile-report-search"
                            name="search"
                            class="form-control"
                            value="<?= esc(
                                        $resolvedSearch,
                                        'attr'
                                    ) ?>"
                            maxlength="100"
                            placeholder="Reporter or reported Profile ID/name">
                    </div>
                </div>

                <div class="col-12 col-md-auto">
                    <button
                        type="submit"
                        class="btn btn-primary
                            d-inline-flex
                            align-items-center
                            gap-1">

                        <i
                            class="ri-filter-3-line"
                            aria-hidden="true">
                        </i>

                        Filter
                    </button>

                    <a
                        href="<?= route_to(
                                    'admin.support.reports'
                                ) ?>"
                        class="btn btn-light">

                        <i
                            class="ri-refresh-line me-1"
                            aria-hidden="true">
                        </i>

                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table
                    class="table
                        table-hover
                        table-nowrap
                        align-middle
                        mb-0">

                    <thead class="bg-info-subtle">
                        <tr>
                            <th scope="col">
                                Reported Member
                            </th>

                            <th scope="col">
                                Reported By
                            </th>

                            <th scope="col">
                                Reason
                            </th>

                            <th scope="col">
                                Status
                            </th>

                            <th scope="col">
                                Submitted
                            </th>

                            <th
                                scope="col"
                                class="text-end">

                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            $resolvedReports === []
                        ): ?>
                            <tr>
                                <td
                                    colspan="6"
                                    class="text-center
                                        text-muted
                                        py-4">

                                    <i
                                        class="ri-flag-line
                                            fs-24
                                            d-block
                                            mb-2"
                                        aria-hidden="true">
                                    </i>

                                    No profile reports were found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (
                            $resolvedReports as $report
                        ): ?>
                            <?php
                            $reportId = (int) (
                                $report['id']
                                ?? 0
                            );

                            $status = mb_strtoupper(
                                trim(
                                    (string) (
                                        $report['status']
                                        ?? 'OPEN'
                                    )
                                )
                            );

                            $isOpen =
                                $status === 'OPEN';

                            $statusLabel = ucwords(
                                mb_strtolower(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $status
                                    )
                                )
                            );

                            $statusClass = match ($status) {
                                'REVIEWED' =>
                                'bg-success-subtle text-success',

                                'DISMISSED' =>
                                'bg-secondary-subtle text-secondary',

                                'ACTION_TAKEN' =>
                                'bg-danger-subtle text-danger',

                                default =>
                                'bg-warning-subtle text-dark',
                            };

                            $submittedAt =
                                DateDisplay::formatUtcDateTime(
                                    $report['created_at']
                                        ?? null
                                );

                            $submittedAtIso =
                                DateDisplay::utcToDisplayIso(
                                    $report['created_at']
                                        ?? null
                                );
                            ?>

                            <tr>
                                <td>
                                    <span class="fw-semibold">
                                        <?= esc(
                                            $report['reported_name']
                                                ?? 'Member'
                                        ) ?>
                                    </span>

                                    <div class="small text-muted">
                                        <?= esc(
                                            $report['reported_profile_reference'] ?? '—'
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <span>
                                        <?= esc(
                                            $report['reporter_name']
                                                ?? 'Member'
                                        ) ?>
                                    </span>

                                    <div class="small text-muted">
                                        <?= esc(
                                            $report['reporter_profile_reference'] ?? '—'
                                        ) ?>
                                    </div>
                                </td>

                                <td class="text-break">
                                    <?= esc(
                                        $report['description']
                                            ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                            <?= esc(
                                                $statusClass,
                                                'attr'
                                            ) ?>
                                            p-2">

                                        <?= esc(
                                            $statusLabel
                                        ) ?>
                                    </span>
                                </td>

                                <td class="text-nowrap">
                                    <?php if (
                                        $submittedAtIso !== ''
                                    ): ?>
                                        <time
                                            datetime="<?= esc(
                                                            $submittedAtIso,
                                                            'attr'
                                                        ) ?>">

                                            <?= esc(
                                                $submittedAt
                                            ) ?>
                                        </time>
                                    <?php else: ?>
                                        <?= esc(
                                            $submittedAt
                                        ) ?>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <?php if ($isOpen): ?>
                                        <button
                                            type="button"
                                            class="btn
                                                btn-sm
                                                btn-soft-primary"
                                            data-support-review-open
                                            data-support-review-type="report"
                                            data-support-review-id="<?= esc(
                                                                        (string) $reportId,
                                                                        'attr'
                                                                    ) ?>"
                                            data-support-review-label="<?= esc(
                                                                            $report['reported_profile_reference'] ?? '',
                                                                            'attr'
                                                                        ) ?>">

                                            <i
                                                class="ri-file-search-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            Review
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            Reviewed
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (
            isset($pager)
            && $pager !== null
        ): ?>
            <div class="card-footer">
                <?= $pager->links(
                    'memberReports',
                    'default_full'
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= view(
    'Admin/Support/_ReviewModal',
    [
        'reviewType' =>
        'report',

        'validationErrors' =>
        $resolvedErrors,

        'reviewRecord' =>
        $reviewRecord ?? [],
    ]
) ?>

<?php $this->endSection(); ?>