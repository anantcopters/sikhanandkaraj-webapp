<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var array<string, mixed> $video
 * @var string $playbackUrl
 * @var list<array<string, mixed>> $videoHistory
 * @var array<string, string>|null $formAlert
 * @var array<string, string> $validationErrors
 * @var list<array<string, mixed>> $memberPhotos
 */

$video =
    isset($video)
    && is_array($video)
    ? $video
    : [];

$videoHistory =
    isset($videoHistory)
    && is_array($videoHistory)
    ? $videoHistory
    : [];

$validationErrors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
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

$mobileNumber = trim(
    (string) (
        $video['mobile_number']
        ?? ''
    )
);

$genderValue = mb_strtoupper(
    trim(
        (string) (
            $video['gender']
            ?? ''
        )
    )
);

$genderLabel = match ($genderValue) {
    'M',
    'MALE' =>
    'Male',

    'F',
    'FEMALE' =>
    'Female',

    default =>
    '—',
};

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

$duration = is_numeric(
    $video['duration_seconds']
        ?? null
)
    ? (float) $video['duration_seconds']
    : null;

$width = is_numeric(
    $video['width']
        ?? null
)
    ? (int) $video['width']
    : null;

$height = is_numeric(
    $video['height']
        ?? null
)
    ? (int) $video['height']
    : null;

$selectedDecision = mb_strtoupper(
    trim(
        (string) old(
            'decision',
            ''
        )
    )
);

$reasonValue = trim(
    (string) old(
        'reason',
        ''
    )
);

$memberPhotos =
    isset($memberPhotos)
    && is_array($memberPhotos)
    ? $memberPhotos
    : [];

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

                    <p class="color-pink mb-0">
                        Review the complete recording and
                        moderation checklist before saving
                        a decision.
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
            'alert' =>
            $formAlert ?? null,
        ]
    ) ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div
                class="card border border-danger
                    border-opacity-25 h-100">

                <div
                    class="card-header bg-info-subtle
        d-flex align-items-center
        justify-content-between gap-3">

                    <div>
                        <h2 class="fs-16 fw-semibold mb-1">
                            <?= esc($memberName) ?>
                        </h2>

                        <p class="text-muted fs-12 mb-0">
                            Profile ID:
                            <strong class="text-body">
                                <?= $reference !== ''
                                    ? esc($reference)
                                    : '—' ?>
                            </strong>
                        </p>
                    </div>
                </div>

                <div class="card-body">
                    <div
                        class="row g-3 fs-13
                            border-bottom pb-3 mb-3">

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">
                                Member ID
                            </span>

                            <strong>
                                <?= $reference !== ''
                                    ? esc($reference)
                                    : '—' ?>
                            </strong>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">
                                Mobile
                            </span>

                            <strong>
                                <?= $mobileNumber !== ''
                                    ? esc($mobileNumber)
                                    : '—' ?>
                            </strong>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">
                                Gender
                            </span>

                            <strong>
                                <?= esc($genderLabel) ?>
                            </strong>
                        </div>

                        <div class="col-12 col-sm-6">
                            <span class="text-muted d-block">
                                Location
                            </span>

                            <strong>
                                <?= $location !== ''
                                    ? esc($location)
                                    : '—' ?>
                            </strong>
                        </div>
                    </div>

                    <div class="border-bottom pb-3 mb-3">
                        <div
                            class="d-flex align-items-center
            justify-content-between gap-2 mb-3">

                            <div>
                                <h3 class="fs-14 fw-semibold mb-1">
                                    Member Photos
                                </h3>

                                <p class="text-muted fs-12 mb-0">
                                    Compare the member with their submitted
                                    Video Introduction.
                                </p>
                            </div>

                            <span class="badge bg-light text-body">
                                <?= count($memberPhotos) ?>
                            </span>
                        </div>

                        <?php if ($memberPhotos === []): ?>
                            <div
                                class="alert alert-warning fs-13 mb-0"
                                role="alert">

                                No retained member photos are available.
                            </div>
                        <?php else: ?>
                            <div
                                class="d-flex flex-nowrap
                overflow-auto gap-3 pb-2">

                                <?php foreach ($memberPhotos as $photo): ?>
                                    <?php
                                    $photoStatus = mb_strtoupper(
                                        trim(
                                            (string) (
                                                $photo['status']
                                                ?? ''
                                            )
                                        )
                                    );

                                    $photoStatusClass = match ($photoStatus) {
                                        'APPROVED' =>
                                        'bg-success',

                                        'REJECTED' =>
                                        'bg-danger',

                                        default =>
                                        'bg-warning text-dark',
                                    };

                                    $thumbnailUrl = trim(
                                        (string) (
                                            $photo['thumbnailUrl']
                                            ?? ''
                                        )
                                    );
                                    ?>

                                    <div
                                        class="card ribbon-box
                        border shadow-none mb-0
                        flex-shrink-0"
                                        style="width: 150px;">

                                        <div
                                            class="ribbon ribbon-<?= esc(
                                                                        str_replace(
                                                                            [
                                                                                'bg-',
                                                                                ' text-dark',
                                                                            ],
                                                                            '',
                                                                            $photoStatusClass
                                                                        ),
                                                                        'attr'
                                                                    ) ?>">

                                            <?= esc(
                                                $photoStatus !== ''
                                                    ? ucfirst(
                                                        mb_strtolower(
                                                            $photoStatus
                                                        )
                                                    )
                                                    : 'Unknown'
                                            ) ?>
                                        </div>

                                        <div class="card-body p-2 pt-4">
                                            <?php if ($thumbnailUrl !== ''): ?>
                                                <img
                                                    src="<?= esc(
                                                                $thumbnailUrl,
                                                                'attr'
                                                            ) ?>"
                                                    alt="Member profile photo"
                                                    class="img-thumbnail
                                    w-100 object-fit-cover"
                                                    style="
                                    height: 145px;
                                    object-position: center;
                                "
                                                    loading="lazy">
                                            <?php else: ?>
                                                <div
                                                    class="bg-light rounded
                                    d-flex align-items-center
                                    justify-content-center"
                                                    style="height: 145px;">

                                                    <i
                                                        class="ri-image-line
                                        fs-24 text-muted"
                                                        aria-hidden="true">
                                                    </i>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (
                                                ($photo['isPrimary'] ?? false)
                                                === true
                                            ): ?>
                                                <div class="text-center mt-2">
                                                    <span
                                                        class="badge
                                        bg-primary-subtle
                                        text-primary">

                                                        Primary
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div
                        class="border rounded p-4
        text-center bg-light">

                        <span
                            class="avatar-lg d-inline-block mb-3">

                            <span
                                class="avatar-title rounded-circle
                bg-primary-subtle
                text-primary">

                                <i
                                    class="ri-video-line fs-24"
                                    aria-hidden="true">
                                </i>
                            </span>
                        </span>

                        <h3 class="fs-16 fw-semibold mb-1">
                            Video Introduction
                        </h3>

                        <p class="text-muted fs-13 mb-3">
                            Play the complete recording before
                            saving the moderation decision.
                        </p>

                        <button
                            type="button"
                            class="btn btn-primary"
                            data-admin-video-open
                            data-video-url="<?= esc(
                                                $playbackUrl,
                                                'attr'
                                            ) ?>"
                            data-member-name="<?= esc(
                                                    $memberName,
                                                    'attr'
                                                ) ?>"
                            data-profile-reference="<?= esc(
                                                        $reference,
                                                        'attr'
                                                    ) ?>">

                            <i
                                class="ri-play-circle-line me-1"
                                aria-hidden="true">
                            </i>

                            Play Video
                        </button>
                    </div>

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
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Video codec
                                    </th>

                                    <td>
                                        <?= trim(
                                            (string) (
                                                $video['video_codec']
                                                ?? ''
                                            )
                                        ) !== ''
                                            ? esc(
                                                (string) $video['video_codec']
                                            )
                                            : '—' ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Audio codec
                                    </th>

                                    <td>
                                        <?= trim(
                                            (string) (
                                                $video['audio_codec']
                                                ?? ''
                                            )
                                        ) !== ''
                                            ? esc(
                                                (string) $video['audio_codec']
                                            )
                                            : '—' ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Resolution
                                    </th>

                                    <td>
                                        <?php if (
                                            $width !== null
                                            && $height !== null
                                            && $width > 0
                                            && $height > 0
                                        ): ?>
                                            <?= esc(
                                                (string) $width
                                            ) ?>
                                            ×
                                            <?= esc(
                                                (string) $height
                                            ) ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
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
                                        <?= trim(
                                            (string) (
                                                $video['consent_version']
                                                ?? ''
                                            )
                                        ) !== ''
                                            ? esc(
                                                (string) $video['consent_version']
                                            )
                                            : '—' ?>
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

                    <span class="avatar-sm">
                        <span
                            class="avatar-title rounded-circle
                                bg-warning-subtle
                                text-warning">

                            <i
                                class="ri-shield-check-line fs-20"
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
                        Confirm every applicable requirement
                        before approving the member's Video
                        Introduction.
                    </p>

                    <ul class="fs-12 ps-3 mb-0">
                        <li class="mb-2">
                            One person is clearly visible
                            and audible.
                        </li>

                        <li class="mb-2">
                            The video is an original,
                            respectful and relevant personal
                            introduction.
                        </li>

                        <li class="mb-2">
                            No phone number, email, address
                            or social-media handle is spoken
                            or displayed.
                        </li>

                        <li class="mb-2">
                            No offensive, misleading or
                            promotional content exists.
                        </li>

                        <li class="mb-2">
                            No other person's private
                            information is disclosed.
                        </li>

                        <li class="mb-2">
                            The video does not contain
                            copyrighted background music.
                        </li>

                        <li>
                            The member does not claim that
                            Sikhanandkaraj.com guarantees their
                            identity.
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="card border border-danger
                    border-opacity-25 mb-0">

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
                        data-video-moderation-form
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
                                class="form-select <?= isset(
                                                        $validationErrors['decision']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                data-choice
                                data-choice-search="false"
                                data-error-required="
            Please select a decision.
        "
                                required>

                                <option value="">
                                    Select a decision
                                </option>

                                <option
                                    value="APPROVE"
                                    <?= $selectedDecision === 'APPROVE'
                                        ? 'selected'
                                        : '' ?>>

                                    Approve
                                </option>

                                <option
                                    value="RESUBMIT"
                                    <?= $selectedDecision === 'RESUBMIT'
                                        ? 'selected'
                                        : '' ?>>

                                    Request resubmission
                                </option>

                                <option
                                    value="REJECT"
                                    <?= $selectedDecision === 'REJECT'
                                        ? 'selected'
                                        : '' ?>>

                                    Reject
                                </option>
                            </select>

                            <div
                                id="decisionError"
                                class="invalid-feedback <?= isset(
                                                            $validationErrors['decision']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="decision">

                                <?= isset(
                                    $validationErrors['decision']
                                )
                                    ? esc(
                                        $validationErrors['decision']
                                    )
                                    : '' ?>
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
                                class="form-control <?= isset(
                                                        $validationErrors['reason']
                                                    )
                                                        ? 'is-invalid'
                                                        : '' ?>"
                                maxlength="500"
                                rows="4"
                                data-error-required="
            Please provide a reason.
        "
                                data-error-minlength="
            Provide a clear reason of at least
            10 characters.
        "
                                data-error-maxlength="
            The reason cannot exceed
            500 characters.
        "
                                placeholder="Provide a clear moderation reason"><?= esc(
                                                                                    $reasonValue
                                                                                ) ?></textarea>

                            <div
                                id="reasonError"
                                class="invalid-feedback <?= isset(
                                                            $validationErrors['reason']
                                                        )
                                                            ? 'd-block'
                                                            : '' ?>"
                                data-validation-error="reason">

                                <?= isset(
                                    $validationErrors['reason']
                                )
                                    ? esc(
                                        $validationErrors['reason']
                                    )
                                    : '' ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <button
                                type="submit"
                                class="btn registration-form__submit w-auto text-uppercase fw-medium"
                                data-submit-button>

                                <span
                                    class="
                                        registration-submit__label
                                    "
                                    data-submit-idle>

                                    <i
                                        class="ri-save-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Save Decision
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
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <?= view(
                'Components/VideoIntroduction/History',
                [
                    'videoHistory' =>
                    $videoHistory,

                    'showTechnicalErrors' =>
                    true,
                ]
            ) ?>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>