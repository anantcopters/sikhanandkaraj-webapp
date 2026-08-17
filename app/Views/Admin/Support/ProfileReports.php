<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $reports
 * @var mixed                      $pager
 * @var string                     $selectedStatus
 * @var string                     $searchTerm
 * @var array<string, string>      $validationErrors
 * @var array<string, mixed>       $reviewRecord
 * @var array<string, mixed>|null  $formAlert
 */

$reports =
    isset($reports)
    && is_array($reports)
    ? $reports
    : [];

$errors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<div class="container-fluid px-3 px-lg-4">

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert ?? null,
        ]
    ) ?>

    <div
        class="d-flex flex-column
            flex-md-row
            justify-content-between
            align-items-md-center
            gap-3 mb-4">

        <div>
            <h1 class="fs-22 fw-semibold mb-1">
                Reported Profiles
            </h1>

            <p class="text-muted mb-0">
                Review member-submitted profile and safety reports.
            </p>
        </div>
    </div>

    <div
        class="card border border-danger
            border-opacity-25 shadow-sm">

        <div class="card-body">

            <form
                method="get"
                action="<?= route_to(
                            'admin.support.reports'
                        ) ?>"
                class="row g-2 mb-4">

                <div class="col-12 col-md-5">
                    <input
                        type="search"
                        name="search"
                        class="form-control"
                        value="<?= esc(
                                    $searchTerm ?? '',
                                    'attr'
                                ) ?>"
                        maxlength="100"
                        placeholder="Search member name or Profile ID">
                </div>

                <div class="col-12 col-md-4">
                    <select
                        name="status"
                        class="form-select">

                        <?php foreach (
                            [
                                'OPEN' => 'Open',
                                'REVIEWED' => 'Reviewed',
                                'DISMISSED' => 'Dismissed',
                                'ACTION_TAKEN' => 'Action Taken',
                                'ALL' => 'All',
                            ] as $value => $label
                        ): ?>
                            <option
                                value="<?= esc(
                                            $value,
                                            'attr'
                                        ) ?>"
                                <?= ($selectedStatus ?? 'OPEN')
                                    === $value
                                    ? 'selected'
                                    : '' ?>>

                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <button
                        type="submit"
                        class="btn btn-primary w-100">

                        Filter
                    </button>
                </div>
            </form>

            <?php if ($reports === []): ?>
                <div class="text-center py-5">
                    <i
                        class="ri-flag-line
                            fs-36 text-muted"
                        aria-hidden="true">
                    </i>

                    <p class="text-muted mt-2 mb-0">
                        No profile reports found.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table
                        class="table
                            table-hover
                            align-middle mb-0">

                        <thead>
                            <tr>
                                <th>Reported Member</th>
                                <th>Reported By</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach (
                                $reports as $report
                            ): ?>
                                <?php
                                $reportId = (int) (
                                    $report['id']
                                    ?? 0
                                );

                                $status = strtoupper(
                                    trim(
                                        (string) (
                                            $report['status']
                                            ?? 'OPEN'
                                        )
                                    )
                                );

                                $isOpen =
                                    $status === 'OPEN';
                                ?>

                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= esc(
                                                $report['reported_name'] ?? 'Member'
                                            ) ?>
                                        </div>

                                        <div class="text-muted fs-12">
                                            <?= esc(
                                                $report['reported_profile_reference'] ?? ''
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div>
                                            <?= esc(
                                                $report['reporter_name'] ?? 'Member'
                                            ) ?>
                                        </div>

                                        <div class="text-muted fs-12">
                                            <?= esc(
                                                $report['reporter_profile_reference'] ?? ''
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div
                                            class="text-break">

                                            <?= esc(
                                                $report['description'] ?? ''
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                <?= $isOpen
                                                    ? 'bg-warning-subtle text-warning'
                                                    : 'bg-success-subtle text-success' ?>
                                                p-2">

                                            <?= esc(
                                                ucwords(
                                                    strtolower(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $status
                                                        )
                                                    )
                                                )
                                            ) ?>
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= esc(
                                            (string) (
                                                $report['created_at'] ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td class="text-end">
                                        <?php if ($isOpen): ?>
                                            <button
                                                type="button"
                                                class="btn
                                                    btn-sm
                                                    btn-outline-primary"
                                                data-support-review-open
                                                data-review-type="report"
                                                data-review-id="<?= esc(
                                                                    (string) $reportId,
                                                                    'attr'
                                                                ) ?>"
                                                data-review-label="<?= esc(
                                                                        $report['reported_profile_reference'] ?? '',
                                                                        'attr'
                                                                    ) ?>">

                                                Review
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted fs-12">
                                                Reviewed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?= $pager->links(
                        'memberReports'
                    ) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= view(
    'Admin/Support/_ReviewModal',
    [
        'reviewType' => 'report',
        'validationErrors' => $errors,
        'reviewRecord' => $reviewRecord ?? [],
    ]
) ?>

<?php $this->endSection(); ?>