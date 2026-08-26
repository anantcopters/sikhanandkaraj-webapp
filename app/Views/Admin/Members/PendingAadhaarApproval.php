<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>> $members
 * @var \CodeIgniter\Pager\Pager $pager
 * @var string $search
 * @var string $selectedStatus
 * @var array<string, string>|null $formAlert
 */

$resolvedMembers =
    isset($members)
    && is_array($members)
    ? $members
    : [];

$resolvedSearch =
    trim(
        (string) (
            $search
            ?? ''
        )
    );

$resolvedStatus =
    mb_strtoupper(
        trim(
            (string) (
                $selectedStatus
                ?? 'UNDER_REVIEW'
            )
        )
    );

$statusOptions = [
    'ALL' =>
    'All Statuses',

    'UNDER_REVIEW' =>
    'Under Review',

    'APPROVED' =>
    'Approved',

    'REJECTED' =>
    'Rejected',
];

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">

    <div
        class="page-title-box
            d-sm-flex
            align-items-sm-center
            justify-content-between
            gap-3">

        <div>

            <h1
                class="fs-18
                    fw-semibold
                    mb-1">

                Aadhaar Authentication

            </h1>

            <p class="text-muted mb-0">
                Review and track Aadhaar documents
                submitted by members.
            </p>

        </div>

    </div>


    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert
                ?? null,
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
                            'admin.members.aadhaar-approvals'
                        ) ?>">

                <div
                    class="row
                        g-2
                        align-items-end">

                    <div
                        class="col-12
                            col-md-5
                            col-xl-4">

                        <label
                            for="aadhaarMemberSearch"
                            class="form-label">

                            Search members

                        </label>

                        <div class="input-group">

                            <span
                                class="input-group-text">

                                <i
                                    class="ri-search-line"
                                    aria-hidden="true">
                                </i>

                            </span>

                            <input
                                type="search"
                                id="aadhaarMemberSearch"
                                name="search"
                                class="form-control"
                                value="<?= esc(
                                            $resolvedSearch,
                                            'attr'
                                        ) ?>"
                                maxlength="100"
                                placeholder="Name or member ID">

                        </div>

                    </div>


                    <div
                        class="col-12
                            col-md-3
                            col-xl-3">

                        <label
                            for="aadhaarStatus"
                            class="form-label">

                            Status

                        </label>

                        <select
                            id="aadhaarStatus"
                            name="status"
                            class="form-select">

                            <?php foreach (
                                $statusOptions
                                as $statusValue =>
                                $statusLabel
                            ): ?>

                                <option
                                    value="<?= esc(
                                                $statusValue,
                                                'attr'
                                            ) ?>"
                                    <?= $resolvedStatus
                                        === $statusValue
                                        ? 'selected'
                                        : '' ?>>

                                    <?= esc(
                                        $statusLabel
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div
                        class="col-6
                            col-md-auto">

                        <button
                            type="submit"
                            class="btn
                                btn-primary
                                w-100">

                            Search

                        </button>

                    </div>


                    <div
                        class="col-6
                            col-md-auto">

                        <a
                            href="<?= route_to(
                                        'admin.members.aadhaar-approvals'
                                    ) ?>"
                            class="btn
                                btn-soft-secondary
                                w-100">

                            Reset

                        </a>

                    </div>

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
                            <th>
                                Member ID
                            </th>

                            <th>
                                Member
                            </th>

                            <th>
                                Age
                            </th>

                            <th>
                                Gender
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Uploaded
                            </th>

                            <th>
                                Status
                            </th>

                            <th class="text-end">
                                Action
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (
                            $resolvedMembers === []
                        ): ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center
                                        py-5">

                                    <i
                                        class="ri-file-search-line
                                            text-muted
                                            fs-24"
                                        aria-hidden="true">
                                    </i>

                                    <h2
                                        class="fs-16
                                            mt-2
                                            mb-1">

                                        No Aadhaar submissions found

                                    </h2>

                                    <p
                                        class="text-muted
                                            mb-0">

                                        No submissions match
                                        the selected filters.

                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>


                        <?php foreach (
                            $resolvedMembers
                            as $member
                        ): ?>

                            <?php
                            $reference =
                                trim(
                                    (string) (
                                        $member['profile_ref_number']
                                        ?? ''
                                    )
                                );

                            $gender =
                                mb_strtoupper(
                                    trim(
                                        (string) (
                                            $member['gender']
                                            ?? ''
                                        )
                                    )
                                );

                            $status =
                                mb_strtoupper(
                                    trim(
                                        (string) (
                                            $member['status']
                                            ?? ''
                                        )
                                    )
                                );

                            $statusLabel =
                                match ($status) {
                                    'APPROVED' =>
                                    'Approved',

                                    'REJECTED' =>
                                    'Rejected',

                                    default =>
                                    'Under Review',
                                };

                            $statusClass =
                                match ($status) {
                                    'APPROVED' =>
                                    'bg-success-subtle text-body p-2',

                                    'REJECTED' =>
                                    'bg-danger-subtle text-body p-2',

                                    default =>
                                    'bg-warning-subtle text-body p-2',
                                };
                            ?>

                            <tr>

                                <td class="fw-semibold">

                                    <?= esc(
                                        $reference
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        (string) (
                                            $member['full_name']
                                            ?? 'Member'
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= is_numeric(
                                        $member['age']
                                            ?? null
                                    )
                                        ? esc(
                                            (string) $member['age']
                                        )
                                        : '—' ?>

                                </td>

                                <td>

                                    <?= $gender === 'M'
                                        ? 'Male'
                                        : (
                                            $gender === 'F'
                                            ? 'Female'
                                            : '—'
                                        ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        trim(
                                            (string) (
                                                $member['location']
                                                ?? ''
                                            )
                                        ) ?: '—'
                                    ) ?>

                                </td>

                                <td>

                                    <?= esc(
                                        DateDisplay
                                            ::formatUtcDateTime(
                                                $member['uploaded_at']
                                                    ?? null
                                            )
                                    ) ?>

                                </td>

                                <td>

                                    <span
                                        class="badge
                                            <?= esc(
                                                $statusClass,
                                                'attr'
                                            ) ?>">

                                        <?= esc(
                                            $statusLabel
                                        ) ?>

                                    </span>

                                </td>

                                <td class="text-end">

                                    <?php if (
                                        $status
                                        === 'UNDER_REVIEW'
                                    ): ?>

                                        <a
                                            href="<?= route_to(
                                                        'admin.members.'
                                                            . 'aadhaar-approvals.review',
                                                        $reference
                                                    ) ?>"
                                            class="btn
                                                btn-sm
                                                btn-soft-primary"
                                            title="Review Aadhaar"
                                            aria-label="<?= esc(
                                                            'Review Aadhaar for '
                                                                . $reference,
                                                            'attr'
                                                        ) ?>">

                                            <i
                                                class="ri-eye-line"
                                                aria-hidden="true">
                                            </i>

                                        </a>

                                    <?php else: ?>

                                        <span
                                            class="text-muted">
                                            —
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
            $resolvedMembers !== []
        ): ?>

            <div class="card-footer">

                <?php
                $pager->only(
                    [
                        'search',
                        'status',
                    ]
                );
                ?>

                <?= $pager->links(
                    'pendingAadhaarMembers',
                    'default_full'
                ) ?>

            </div>

        <?php endif; ?>

    </div>

</div>

<?= $this->endSection() ?>