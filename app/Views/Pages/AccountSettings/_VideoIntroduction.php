<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var array<string, mixed>|null $videoIntroduction
 * @var array<string, mixed>|null $activeVideoIntroduction
 * @var list<array<string, mixed>> $videoIntroductionHistory
 * @var string $videoStatus
 * @var string $videoStatusLabel
 * @var bool $isFemaleMember
 * @var bool $isProMember
 * @var bool $canRecord
 * @var bool $canDelete
 * @var bool $canHide
 * @var bool $isHidden
 * @var int $lockRemainingSeconds
 * @var list<string> $allowedVisibilities
 * @var bool $hasApprovedProfilePhoto
 * @var string $videoMemberName
 * @var string $videoProfileReference
 */

$videoIntroduction =
    isset($videoIntroduction)
    && is_array($videoIntroduction)
    ? $videoIntroduction
    : null;

$activeVideoIntroduction =
    isset($activeVideoIntroduction)
    && is_array($activeVideoIntroduction)
    ? $activeVideoIntroduction
    : null;

$videoIntroductionHistory =
    isset($videoIntroductionHistory)
    && is_array($videoIntroductionHistory)
    ? $videoIntroductionHistory
    : [];

$videoStatus = mb_strtoupper(
    trim(
        $videoStatus
            ?? 'NOT_SUBMITTED'
    )
);

$videoStatusLabel = trim(
    $videoStatusLabel
        ?? 'Not submitted'
);

$allowedVisibilities =
    isset($allowedVisibilities)
    && is_array($allowedVisibilities)
    ? $allowedVisibilities
    : [];

$selectedVisibility =
    is_array($activeVideoIntroduction)
    ? (string) (
        $activeVideoIntroduction['visibility']
        ?? 'HIDDEN'
    )
    : 'HIDDEN';

$reason = is_array($videoIntroduction)
    ? trim(
        (string) (
            $videoIntroduction['rejection_reason']
            ?? ''
        )
    )
    : '';

$duration = is_array($videoIntroduction)
    && is_numeric(
        $videoIntroduction['duration_seconds']
            ?? null
    )
    ? (float) $videoIntroduction['duration_seconds']
    : null;

$statusClass = match ($videoStatus) {
    'APPROVED' =>
    'bg-success-subtle text-success',

    'REJECTED',
    'PROCESSING_FAILED' =>
    'bg-danger-subtle text-danger',

    'PENDING_REVIEW',
    'RESUBMISSION_REQUESTED' =>
    'bg-warning-subtle text-warning',

    'PROCESSING' =>
    'bg-primary-subtle text-primary',

    'DELETED',
    'REPLACED' =>
    'bg-secondary-subtle text-secondary',

    default =>
    'bg-light text-body',
};
?>

<div
    class="d-flex flex-wrap align-items-start
        justify-content-between gap-3 mb-3">

    <div>
        <h2 class="fs-18 fw-semibold mb-1">
            Video Introduction
        </h2>

        <p class="text-muted fs-13 mb-0">
            Manage your recorded introduction and
            who can view it.
        </p>
    </div>

    <span
        class="badge <?= esc(
                            $statusClass,
                            'attr'
                        ) ?> p-2">

        <?= esc($videoStatusLabel) ?>
    </span>
</div>

<?php if ($reason !== ''): ?>
    <div
        class="alert alert-warning"
        role="alert">

        <strong>Moderator feedback:</strong>

        <?= esc($reason) ?>
    </div>
<?php endif; ?>

<?php if (is_array($videoIntroduction)): ?>
    <dl class="row fs-13 mb-4">
        <dt class="col-sm-4">
            Submitted
        </dt>

        <dd class="col-sm-8">
            <?= esc(
                DateDisplay::formatUtcDateTime(
                    $videoIntroduction['submitted_at']
                        ?? null
                )
            ) ?>
        </dd>

        <dt class="col-sm-4">
            Duration
        </dt>

        <dd class="col-sm-8">
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
            <?php elseif (
                in_array(
                    $videoStatus,
                    [
                        'PROCESSING',
                        'PENDING_REVIEW',
                    ],
                    true
                )
            ): ?>
                <span class="text-primary">
                    Duration will appear after the
                    video is approved.
                </span>
            <?php else: ?>
                <span class="text-muted">
                    Duration unavailable
                </span>
            <?php endif; ?>
        </dd>
    </dl>
<?php endif; ?>

<?php if (is_array($activeVideoIntroduction)): ?>
    <div class="mb-4">
        <button
            type="button"
            class="btn btn-primary"
            data-video-introduction-open
            data-playback-url="<?= esc(
                                    route_to(
                                        'web.video-introduction.owner-playback'
                                    ),
                                    'attr'
                                ) ?>"
            data-member-name="<?= esc(
                                    (string) (
                                        $videoMemberName
                                        ?? 'Video Introduction'
                                    ),
                                    'attr'
                                ) ?>"
            data-profile-reference="<?= esc(
                                        (string) (
                                            $videoProfileReference
                                            ?? ''
                                        ),
                                        'attr'
                                    ) ?>"
            data-hidden="0">

            <i
                class="ri-play-circle-line me-1"
                aria-hidden="true">
            </i>

            Preview Video
        </button>
    </div>

    <form
        method="post"
        action="<?= route_to(
                    'web.video-introduction.visibility'
                ) ?>"
        data-submit-loader
        class="border-top pt-3 mb-4">

        <?= csrf_field() ?>

        <label
            for="videoVisibility"
            class="form-label fw-semibold">

            Who can view this video?
        </label>

        <select
            id="videoVisibility"
            name="video_visibility"
            class="form-select"
            data-choice
            data-choice-search="false"
            required>

            <?php if (
                in_array(
                    'VISIBLE_PRO',
                    $allowedVisibilities,
                    true
                )
            ): ?>
                <option
                    value="VISIBLE_PRO"
                    <?= $selectedVisibility
                        === 'VISIBLE_PRO'
                        ? 'selected'
                        : '' ?>>

                    Visible to Pro members
                </option>
            <?php endif; ?>

            <option
                value="VISIBLE_AFTER_ACCEPTED_INTEREST"
                <?= $selectedVisibility
                    === 'VISIBLE_AFTER_ACCEPTED_INTEREST'
                    ? 'selected'
                    : '' ?>>
                Only after Interest is accepted
            </option>

            <option
                value="HIDDEN"
                <?= $selectedVisibility === 'HIDDEN'
                    ? 'selected'
                    : '' ?>>

                Hidden
            </option>
        </select>

        <?php if ($isFemaleMember): ?>
            <div class="form-text color-pink">
                For female profiles, public Pro
                visibility is unavailable.
            </div>
        <?php endif; ?>

        <div class="text-end mt-3">
            <button
                type="submit"
                class="btn btn-danger"
                data-submit-button>

                <span
                    class="registration-submit__label"
                    data-submit-idle>

                    <i
                        class="ri-save-line me-1"
                        aria-hidden="true">
                    </i>

                    Save Privacy
                </span>

                <span
                    class="
                        registration-submit__loading
                        d-none
                    "
                    data-submit-loading>

                    <span
                        class="spinner-border
                            spinner-border-sm me-1"
                        aria-hidden="true">
                    </span>

                    Saving...
                </span>
            </button>
        </div>
    </form>
<?php endif; ?>

<?php if (!$hasApprovedProfilePhoto): ?>
    <div
        class="alert alert-warning fs-13"
        role="alert">

        <div class="d-flex align-items-start gap-2">
            <i
                class="ri-image-add-line fs-18"
                aria-hidden="true">
            </i>

            <div>
                <strong>
                    An approved profile photo is required
                </strong>

                <p class="mb-0 mt-1">
                    Add a profile photo and wait for its
                    approval before recording your Video
                    Introduction.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="border-top pt-3">
    <?php if ($lockRemainingSeconds > 0): ?>
        <p class="color-pink fs-13 mb-3">
            Delete/replace unlocks after the
            seven-day lock. You may hide it now.
        </p>
    <?php endif; ?>

    <div
        class="d-flex flex-wrap
            justify-content-end gap-2">

        <?php if ($canRecord): ?>
            <a
                href="<?= route_to(
                            'web.video-introduction.record'
                        ) ?>"
                class="btn btn-outline-danger">

                <i
                    class="ri-video-add-line me-1"
                    aria-hidden="true">
                </i>

                <?= is_array($videoIntroduction)
                    ? 'Record Replacement'
                    : 'Record Video' ?>
            </a>
        <?php endif; ?>

        <?php if ($canDelete): ?>
            <form
                method="post"
                action="<?= route_to(
                            'web.video-introduction.delete'
                        ) ?>"
                onsubmit="return confirm(
                    'Delete this Video Introduction? '
                    + 'Its badge will be removed.'
                );"
                data-submit-loader>

                <?= csrf_field() ?>

                <button
                    type="submit"
                    class="btn btn-outline-danger"
                    data-submit-button>

                    <span
                        class="
                            registration-submit__label
                        "
                        data-submit-idle>

                        <i
                            class="ri-delete-bin-line me-1"
                            aria-hidden="true">
                        </i>

                        Delete
                    </span>

                    <span
                        class="
                            registration-submit__loading
                            d-none
                        "
                        data-submit-loading>

                        <span
                            class="spinner-border
                                spinner-border-sm me-1"
                            aria-hidden="true">
                        </span>

                        Deleting...
                    </span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4">
    <?= view(
        'Components/VideoIntroduction/History',
        [
            'videoHistory' =>
            $videoIntroductionHistory,

            /*
             * Members should receive understandable moderation
             * feedback, but internal FFmpeg/S3 processing details
             * must only be available to administrators.
             */
            'showTechnicalErrors' =>
            false,
        ]
    ) ?>
</div>