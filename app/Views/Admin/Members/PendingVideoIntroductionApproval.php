<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>> $videos
 * @var string $search
 * @var array<string, string>|null $formAlert
 */

$videos =
    isset($videos)
    && is_array($videos)
    ? $videos
    : [];

$search = trim(
    (string) ($search ?? '')
);

$this->extend('Admin/Layouts/Main');

$this->section('content');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div
                class="page-title-box
                    d-sm-flex align-items-sm-center
                    justify-content-between gap-3">

                <div>
                    <h1 class="fs-18 fw-semibold mb-1">
                        Video Introduction Approvals
                    </h1>

                    <p class="text-muted mb-0">
                        Review processed member introductions
                        before they become visible on profiles.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' => $formAlert ?? null,
        ]
    ) ?>

    <div
        class="card border border-danger
            border-opacity-25">

        <div class="card-header">
            <form
                method="get"
                action="<?= route_to(
                            'admin.members.video-introductions'
                        ) ?>">

                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-xl-4">
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
                                placeholder="Name or reference number">
                        </div>
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
                    class="table table-hover table-nowrap
                        align-middle mb-0">

                    <thead class="bg-info-subtle">
                        <tr>
                            <th>Reference</th>
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
                                                    bg-success-subtle
                                                    text-success fs-24">

                                                <i
                                                    class="ri-checkbox-circle-line"
                                                    aria-hidden="true">
                                                </i>
                                            </span>
                                        </div>

                                        <h2 class="fs-16 mb-1">
                                            <?= $search !== ''
                                                ? 'No matching submissions'
                                                : 'No videos pending approval' ?>
                                        </h2>

                                        <p class="text-muted mb-0">
                                            <?= $search !== ''
                                                ? 'No pending Video Introduction matches your search.'
                                                : 'There are currently no Video Introductions waiting for review.' ?>
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

                            $duration = is_numeric(
                                $video['duration_seconds']
                                    ?? null
                            )
                                ? number_format(
                                    (float) $video['duration_seconds'],
                                    1
                                )
                                : null;

                            $publicId = trim(
                                (string) (
                                    $video['public_id']
                                    ?? ''
                                )
                            );
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
                                    <?= $gender === 'M'
                                        ? 'Male'
                                        : (
                                            $gender === 'F'
                                            ? 'Female'
                                            : '—'
                                        ) ?>
                                </td>

                                <td>
                                    <?= $duration !== null
                                        ? esc($duration) . ' seconds'
                                        : '—' ?>
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
                                        class="badge bg-warning-subtle
                                            text-warning">

                                        Under Review
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
                                        title="Review Video Introduction"
                                        aria-label="Review Video Introduction for <?= esc(
                                                                                        $reference,
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