<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>> $videos
 * @var string $search
 * @var string $selectedStatus
 * @var array<string, string>|null $formAlert
 */

$videos =
    isset($videos)
    && is_array($videos)
    ? $videos
    : [];

$search = trim(
    (string) (
        $search
        ?? ''
    )
);

$selectedStatus = mb_strtoupper(
    trim(
        (string) (
            $selectedStatus
            ?? 'PENDING_REVIEW'
        )
    )
);

$statusOptions = [
    'ALL' =>
    'All Statuses',

    'PROCESSING' =>
    'Processing',

    'PROCESSING_FAILED' =>
    'Processing Failed',

    'PENDING_REVIEW' =>
    'Pending Review',

    'APPROVED' =>
    'Approved',

    'REJECTED' =>
    'Rejected',

    'RESUBMISSION_REQUESTED' =>
    'Resubmission Requested',

    'REPLACED' =>
    'Replaced',

    'DELETED' =>
    'Deleted',
];

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
                    <h1 class="fs-18 fw-semibold mb-1">
                        Video Introduction Approvals
                    </h1>

                    <p class="text-muted mb-0">
                        Search and review member Video
                        Introduction submissions.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert ?? null,
        ]
    ) ?>

    <div
        class="card border border-danger
            border-opacity-25">

        <div class="card-header">
            <form
                method="get"
                action="<?= route_to(
                            'admin.members'
                                . '.video-introductions'
                        ) ?>">

                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5 col-xl-4">
                        <label
                            for="videoMemberSearch"
                            class="form-label">

                            Search members
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
                                id="videoMemberSearch"
                                name="search"
                                class="form-control"
                                value="<?= esc(
                                            $search,
                                            'attr'
                                        ) ?>"
                                maxlength="100"
                                placeholder="Name or profile ID">
                        </div>
                    </div>

                    <div class="col-12 col-md-4 col-xl-3">
                        <label
                            for="videoStatus"
                            class="form-label">

                            Status
                        </label>

                        <select
                            id="videoStatus"
                            name="status"
                            class="form-select"
                            data-choice
                            data-choice-search="false">

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
                                    <?= $selectedStatus
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

                    <div class="col-6 col-md-auto">
                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            <i
                                class="ri-search-line me-1"
                                aria-hidden="true">
                            </i>

                            Search
                        </button>
                    </div>

                    <div class="col-6 col-md-auto">
                        <a
                            href="<?= route_to(
                                        'admin.members'
                                            . '.video-introductions'
                                    ) ?>"
                            class="btn btn-soft-secondary w-100">

                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table
                    class="table table-hover
                        table-nowrap align-middle mb-0">

                    <thead class="bg-info-subtle">
                        <tr>
                            <th>Profile ID</th>
                            <th>Member</th>
                            <th>Gender</th>
                            <th>Duration</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th class="text-end">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($videos === []): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="text-center py-5">
                                        <div
                                            class="avatar-md
                                                mx-auto mb-3">

                                            <span
                                                class="avatar-title
                                                    rounded-circle
                                                    bg-light
                                                    text-muted fs-24">

                                                <i
                                                    class="ri-video-line"
                                                    aria-hidden="true">
                                                </i>
                                            </span>
                                        </div>

                                        <h2 class="fs-16 mb-1">
                                            No submissions found
                                        </h2>

                                        <p class="text-muted mb-0">
                                            No Video Introductions
                                            match the selected filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($videos as $video): ?>
                            <?php
                            $reference = trim(
                                (string) (
                                    $video['profile_ref_number']
                                    ?? ''
                                )
                            );

                            $memberName = trim(
                                (string) (
                                    $video['full_name']
                                    ?? 'Member'
                                )
                            );

                            $gender = mb_strtoupper(
                                trim(
                                    (string) (
                                        $video['gender']
                                        ?? ''
                                    )
                                )
                            );

                            $genderLabel = match ($gender) {
                                'M',
                                'MALE' =>
                                'Male',

                                'F',
                                'FEMALE' =>
                                'Female',

                                default =>
                                '—',
                            };

                            $duration = is_numeric(
                                $video['duration_seconds']
                                    ?? null
                            )
                                ? (float) $video['duration_seconds']
                                : null;

                            $publicId = trim(
                                (string) (
                                    $video['public_id']
                                    ?? ''
                                )
                            );

                            $status = mb_strtoupper(
                                trim(
                                    (string) (
                                        $video['moderation_status'] ?? ''
                                    )
                                )
                            );

                            $statusPresentation = match ($status) {
                                'APPROVED' => [
                                    'Approved',
                                    'bg-success-subtle text-body p-2',
                                ],

                                'REJECTED' => [
                                    'Rejected',
                                    'bg-danger-subtle text-body p-2',
                                ],

                                'PROCESSING_FAILED' => [
                                    'Processing Failed',
                                    'bg-danger-subtle text-body p-2',
                                ],

                                'RESUBMISSION_REQUESTED' => [
                                    'Resubmission Requested',
                                    'bg-warning-subtle text-body p-2',
                                ],

                                'PENDING_REVIEW' => [
                                    'Pending Review',
                                    'bg-warning-subtle text-body p-2',
                                ],

                                'PROCESSING' => [
                                    'Processing',
                                    'bg-primary-subtle text-body p-2',
                                ],

                                'REPLACED' => [
                                    'Replaced',
                                    'bg-secondary-subtle text-body p-2',
                                ],

                                'DELETED' => [
                                    'Deleted',
                                    'bg-secondary-subtle text-body p-2',
                                ],

                                default => [
                                    'Unknown',
                                    'bg-light text-muted',
                                ],
                            };
                            ?>

                            <tr>
                                <td>
                                    <span class="fw-semibold">
                                        <?= $reference !== ''
                                            ? esc($reference)
                                            : '—' ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-medium">
                                        <?= esc($memberName) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= esc($genderLabel) ?>
                                </td>

                                <td>
                                    <?php if (
                                        $duration !== null
                                        && $duration > 0
                                    ): ?>
                                        <?= esc(
                                            number_format(
                                                $duration,
                                                1
                                            )
                                        ) ?>
                                        seconds
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= esc(
                                        DateDisplay::formatUtcDateTime(
                                            $video['submitted_at']
                                                ?? null
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="badge <?= esc(
                                                            $statusPresentation[1],
                                                            'attr'
                                                        ) ?>">

                                        <?= esc(
                                            $statusPresentation[0]
                                        ) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a
                                        class="btn btn-sm
                                            btn-soft-primary"
                                        href="<?= route_to(
                                                    'admin.members'
                                                        . '.video-introductions'
                                                        . '.review',
                                                    $publicId
                                                ) ?>"
                                        title="View Video Introduction"
                                        aria-label="<?= esc(
                                                        'View Video Introduction for '
                                                            . $reference,
                                                        'attr'
                                                    ) ?>">

                                        <i
                                            class="ri-eye-line"
                                            aria-hidden="true">
                                        </i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>