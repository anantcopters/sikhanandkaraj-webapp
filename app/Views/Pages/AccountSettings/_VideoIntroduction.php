<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>|null $videoIntroduction
 * @var array<string, mixed>|null $activeVideoIntroduction
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

    <span class="badge bg-light text-body border p-2">
        <?= esc($videoStatusLabel) ?>
    </span>
</div>

<?php if ($reason !== ''): ?>
    <div class="alert alert-warning" role="alert">
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
                (string) (
                    $videoIntroduction['submitted_at']
                    ?? ''
                )
            ) ?>
        </dd>

        <dt class="col-sm-4">
            Duration
        </dt>

        <dd class="col-sm-8">
            <?= esc(
                number_format(
                    (float) (
                        $videoIntroduction['duration_seconds']
                        ?? 0
                    ),
                    1
                )
            ) ?>
            seconds
        </dd>
    </dl>
<?php endif; ?>

<?php if (is_array($activeVideoIntroduction)): ?>
    <div class="mb-4">
        <button
            type="button"
            class="btn btn-outline-primary"
            data-video-introduction-open
            data-playback-url="<?= esc(
                                    route_to(
                                        'web.video-introduction.owner-playback'
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
                    <?= $selectedVisibility === 'VISIBLE_PRO'
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
            <div class="form-text">
                For female profiles, public Pro visibility
                is unavailable.
            </div>
        <?php endif; ?>

        <div class="text-end mt-3">
            <button
                type="submit"
                class="btn btn-danger"
                data-submit-button>

                <span data-submit-idle>
                    <i
                        class="ri-save-line me-1"
                        aria-hidden="true">
                    </i>

                    Save Privacy
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
<?php endif; ?>

<div
    class="d-flex flex-wrap justify-content-end
        gap-2 border-top pt-3">

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

                <span data-submit-idle>
                    <i
                        class="ri-delete-bin-line me-1"
                        aria-hidden="true">
                    </i>

                    Delete
                </span>

                <span
                    class="d-none"
                    data-submit-loading>

                    <span
                        class="spinner-border
                            spinner-border-sm me-1">
                    </span>

                    Deleting...
                </span>
            </button>
        </form>
    <?php elseif ($lockRemainingSeconds > 0): ?>
        <span
            class="text-muted fs-13 align-self-center">

            Delete/replace unlocks after the
            seven-day lock. You may hide it now.
        </span>
    <?php endif; ?>
</div>