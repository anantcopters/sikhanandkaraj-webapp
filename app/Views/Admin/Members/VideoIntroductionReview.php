<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var array<string, mixed> $video
 * @var string $playbackUrl
 * @var string $posterUrl
 * @var list<array<string, mixed>> $history
 * @var array<string, string>|null $formAlert
 */

$video =
    isset($video)
    && is_array($video)
    ? $video
    : [];

$history =
    isset($history)
    && is_array($history)
    ? $history
    : [];

$publicId = trim(
    (string) (
        $video['public_id']
        ?? ''
    )
);

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
                        Review Video Introduction
                    </h1>

                    <p class="text-muted mb-0">
                        Review the complete recording and moderation
                        checklist before saving a decision.
                    </p>
                </div>

                <a
                    href="<?= route_to(
                                'admin.members.video-introductions'
                            ) ?>"
                    class="btn btn-soft-secondary">

                    <i
                        class="ri-arrow-left-line me-1"
                        aria-hidden="true">
                    </i>

                    Back
                </a>
            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' => $formAlert ?? null,
        ]
    ) ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div
                class="card border border-danger
                    border-opacity-25">

                <div
                    class="card-header bg-transparent
                        d-flex align-items-center
                        justify-content-between gap-3">

                    <div>
                        <h2 class="fs-16 fw-semibold mb-1">
                            <?= esc($memberName) ?>
                        </h2>

                        <p class="text-muted fs-12 mb-0">
                            <?= $reference !== ''
                                ? esc($reference)
                                : 'Reference unavailable' ?>
                        </p>
                    </div>

                    <?php
                    $location = implode(
                        ', ',
                        array_filter([
                            trim(
                                (string) (
                                    $video['city_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $video['state_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $video['country_name']
                                    ?? ''
                                )
                            ),
                        ])
                    );
                    ?>

                    <div class="row g-3 mt-1 fs-13">
                        <div class="col-6">
                            <span class="text-muted d-block">
                                Member ID
                            </span>

                            <strong>
                                <?= esc(
                                    $reference !== ''
                                        ? $reference
                                        : '—'
                                ) ?>
                            </strong>
                        </div>

                        <div class="col-6">
                            <span class="text-muted d-block">
                                Mobile
                            </span>

                            <strong>
                                <?= esc(
                                    (string) (
                                        $video['mobile_number']
                                        ?? '—'
                                    )
                                ) ?>
                            </strong>
                        </div>

                        <div class="col-6">
                            <span class="text-muted d-block">
                                Gender
                            </span>

                            <strong>
                                <?= esc(
                                    (string) (
                                        $video['gender']
                                        ?? '—'
                                    )
                                ) ?>
                            </strong>
                        </div>

                        <div class="col-6">
                            <span class="text-muted d-block">
                                Location
                            </span>

                            <strong>
                                <?= esc(
                                    $location !== ''
                                        ? $location
                                        : '—'
                                ) ?>
                            </strong>
                        </div>
                    </div>

                    <span
                        class="badge bg-warning-subtle
                            text-warning">

                        Under Review
                    </span>
                </div>

                <div class="card-body">
                    <video
                        class="w-100 rounded border"
                        controls
                        playsinline
                        preload="metadata"
                        poster="<?= esc(
                                    $posterUrl,
                                    'attr'
                                ) ?>">

                        <source
                            src="<?= esc(
                                        $playbackUrl,
                                        'attr'
                                    ) ?>"
                            type="video/mp4">
                    </video>

                    <div class="table-responsive mt-3">
                        <table
                            class="table table-sm
                                table-nowrap mb-0">

                            <tbody>
                                <tr>
                                    <th class="text-muted">
                                        Duration
                                    </th>

                                    <td>
                                        <?= esc(
                                            number_format(
                                                (float) (
                                                    $video['duration_seconds']
                                                    ?? 0
                                                ),
                                                1
                                            )
                                        ) ?>
                                        seconds
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Video codec
                                    </th>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $video['video_codec']
                                                ?? '—'
                                            )
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Audio codec
                                    </th>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $video['audio_codec']
                                                ?? '—'
                                            )
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Resolution
                                    </th>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $video['width']
                                                ?? '—'
                                            )
                                        ) ?>
                                        ×
                                        <?= esc(
                                            (string) (
                                                $video['height']
                                                ?? '—'
                                            )
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Submitted
                                    </th>

                                    <td>
                                        <?= esc(
                                            DateDisplay::formatUtcDateTime(
                                                $video['submitted_at']
                                                    ?? null
                                            )
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Consent version
                                    </th>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $video['consent_version']
                                                ?? '—'
                                            )
                                        ) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div
                class="card border border-warning
                    border-opacity-50 mb-4">

                <div
                    class="card-header bg-transparent
                        d-flex align-items-center gap-2">

                    <span class="avatar-xs">
                        <span
                            class="avatar-title rounded-circle
                                bg-warning-subtle text-warning">

                            <i
                                class="ri-shield-check-line"
                                aria-hidden="true">
                            </i>
                        </span>
                    </span>

                    <h2 class="fs-16 fw-semibold mb-0">
                        Moderation checklist
                    </h2>
                </div>

                <div class="card-body">
                    <p class="fs-13 text-muted">
                        Confirm every applicable requirement before
                        approving the member's Video Introduction.
                    </p>

                    <ul class="fs-12 ps-3 mb-0">
                        <li class="mb-2">
                            One person is clearly visible and audible.
                        </li>

                        <li class="mb-2">
                            The video is an original, respectful and
                            relevant personal introduction.
                        </li>

                        <li class="mb-2">
                            No phone number, email, address or
                            social-media handle is spoken or displayed.
                        </li>

                        <li class="mb-2">
                            No offensive, misleading or promotional
                            content exists.
                        </li>

                        <li class="mb-2">
                            No other person's private information is
                            disclosed.
                        </li>

                        <li class="mb-2">
                            The video does not contain copyrighted
                            background music.
                        </li>

                        <li>
                            The member does not claim SikhanAndKaraj
                            guarantees their identity.
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <?= view(
                        'Components/VideoIntroduction/History',
                        [
                            'videoHistory' =>
                            $videoHistory ?? [],

                            'showTechnicalErrors' =>
                            true,
                        ]
                    ) ?>
                </div>
            </div>

            <div
                class="card border border-danger
                    border-opacity-25">

                <div class="card-header bg-transparent">
                    <h2 class="fs-16 fw-semibold mb-0">
                        Moderation decision
                    </h2>
                </div>

                <div class="card-body">
                    <form
                        method="post"
                        action="<?= route_to(
                                    'admin.members'
                                        . '.video-introductions'
                                        . '.moderate',
                                    $publicId
                                ) ?>"
                        data-validate
                        data-submit-loader
                        novalidate>

                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label
                                for="decision"
                                class="form-label">

                                Decision
                            </label>

                            <select
                                id="decision"
                                name="decision"
                                class="form-select"
                                data-choice
                                data-choice-search="false"
                                required>

                                <option value="">
                                    Select a decision
                                </option>

                                <option value="APPROVE">
                                    Approve
                                </option>

                                <option value="RESUBMIT">
                                    Request resubmission
                                </option>

                                <option value="REJECT">
                                    Reject
                                </option>
                            </select>

                            <div class="invalid-feedback">
                                Please select a decision.
                            </div>

                            <div class="invalid-feedback">
                                Please select a decision.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label
                                for="reason"
                                class="form-label">

                                Reason

                                <span class="text-muted fs-12">
                                    (required for rejection or
                                    resubmission)
                                </span>
                            </label>

                            <textarea
                                id="reason"
                                name="reason"
                                class="form-control"
                                minlength="10"
                                maxlength="500"
                                rows="4"
                                placeholder="Provide a clear moderation reason"></textarea>

                            <div class="invalid-feedback">
                                Use between 10 and 500 characters.
                            </div>
                        </div>

                        <div class="text-end">
                            <button
                                type="submit"
                                class="btn btn-danger"
                                data-submit-button>

                                <span data-submit-idle>
                                    <i
                                        class="ri-save-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Save Decision
                                </span>

                                <span
                                    class="d-none"
                                    data-submit-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm me-1">
                                    </span>

                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>