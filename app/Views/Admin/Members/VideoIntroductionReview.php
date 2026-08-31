<?php

declare(strict_types=1);

use App\Models\MemberPhotoModel;
use App\Support\DateDisplay;
use App\Models\MemberVideoIntroductionModel;

/**
 * @var array<string, mixed> $video
 * @var string $playbackUrl
 * @var list<array<string, mixed>> $videoHistory
 * @var list<array<string, mixed>> $memberPhotos
 * @var array<string, mixed> $trustVerification
 * @var array<string, string>|null $formAlert
 * @var array<string, string> $validationErrors
 * @var bool $canModerate
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

$memberPhotos =
    isset($memberPhotos)
    && is_array($memberPhotos)
    ? $memberPhotos
    : [];

$trustVerification =
    isset($trustVerification)
    && is_array($trustVerification)
    ? $trustVerification
    : [];

$validationErrors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$playbackUrl = trim(
    (string) (
        $playbackUrl
        ?? ''
    )
);

$canModerate =
    ($canModerate ?? false)
    === true;

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

if ($memberName === '') {
    $memberName = 'Member';
}

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
    array_filter(
        [
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
        ],
        static fn(string $value): bool =>
        $value !== ''
    )
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

$videoCodec = trim(
    (string) (
        $video['video_codec']
        ?? ''
    )
);

$audioCodec = trim(
    (string) (
        $video['audio_codec']
        ?? ''
    )
);

$consentVersion = trim(
    (string) (
        $video['consent_version']
        ?? ''
    )
);

$moderationStatus = mb_strtoupper(
    trim(
        (string) (
            $video['moderation_status']
            ?? ''
        )
    )
);

$moderationStatusPresentation = match ($moderationStatus) {
    MemberVideoIntroductionModel::STATUS_APPROVED => [
        'label' =>
        'Approved',

        'class' =>
        'bg-success-subtle text-black p-2',
    ],

    MemberVideoIntroductionModel::STATUS_REJECTED => [
        'label' =>
        'Rejected',

        'class' =>
        'bg-danger-subtle text-black p-2',
    ],

    MemberVideoIntroductionModel::STATUS_PROCESSING_FAILED => [
        'label' =>
        'Processing Failed',

        'class' =>
        'bg-danger-subtle text-black p-2',
    ],

    MemberVideoIntroductionModel::STATUS_RESUBMISSION_REQUESTED => [
        'label' =>
        'Resubmission Requested',

        'class' =>
        'bg-warning-subtle text-black p-2',
    ],

    MemberVideoIntroductionModel::STATUS_PENDING_REVIEW => [
        'label' =>
        'Pending Review',

        'class' =>
        'bg-warning-subtle text-black p-2',
    ],

    MemberVideoIntroductionModel::STATUS_PROCESSING => [
        'label' =>
        'Processing',

        'class' =>
        'bg-primary-subtle text-black p-2',
    ],

    MemberVideoIntroductionModel::STATUS_REPLACED => [
        'label' =>
        'Replaced',

        'class' =>
        'bg-secondary-subtle text-black p-2',
    ],

    MemberVideoIntroductionModel::STATUS_DELETED => [
        'label' =>
        'Deleted',

        'class' =>
        'bg-secondary-subtle text-black p-2',
    ],

    default => [
        'label' =>
        'Unknown',

        'class' =>
        'bg-light text-muted',
    ],
};

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
                        Review Video Introduction
                    </h1>

                    <p class="text-muted mb-0">
                        Review the member, recording and
                        moderation requirements.
                    </p>
                </div>

                <div class="mt-3 mt-sm-0">
                    <a
                        href="<?= route_to(
                                    'admin.members'
                                        . '.video-introductions'
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
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert ?? null,
        ]
    ) ?>

    <div class="row g-4 align-items-start">
        <!-- Left column -->
        <div class="col-12 col-xl-7">
            <!-- Member overview -->
            <section
                class="card border border-danger
                    border-opacity-25 mb-4"
                aria-labelledby="videoMemberTitle">

                <div
                    class="card-header bg-info-subtle
                        d-flex align-items-center
                        justify-content-between gap-3">

                    <div>
                        <h2
                            id="videoMemberTitle"
                            class="fs-16 fw-semibold mb-1">

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
                    <div class="row g-3 fs-13">
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
                </div>
            </section>

            <!-- Member photos -->
            <section
                class="card border border-danger
                    border-opacity-25 mb-4"
                aria-labelledby="videoMemberPhotosTitle">

                <div
                    class="card-header
                        d-flex align-items-center
                        justify-content-between gap-3">

                    <div>
                        <h2
                            id="videoMemberPhotosTitle"
                            class="card-title fs-16 mb-1">

                            <i
                                class="ri-gallery-line me-1"
                                aria-hidden="true">
                            </i>

                            Member Photographs
                        </h2>

                        <p class="text-muted fs-12 mb-0">
                            Compare the member with the submitted
                            Video Introduction.
                        </p>
                    </div>

                    <span
                        class="badge
                            bg-primary-subtle
                            text-body p-2">

                        <?= esc(
                            (string) count(
                                $memberPhotos
                            )
                        ) ?>
                    </span>
                </div>

                <div class="card-body">
                    <?php if ($memberPhotos === []): ?>
                        <div
                            class="border rounded-3
                                text-center text-muted p-4">

                            <i
                                class="ri-image-line
                                    fs-28 d-block mb-2"
                                aria-hidden="true">
                            </i>

                            <p class="mb-0">
                                No retained member photographs
                                are available.
                            </p>
                        </div>
                    <?php else: ?>
                        <div
                            class="row flex-nowrap
                                overflow-auto g-3 pb-2">

                            <?php foreach (
                                $memberPhotos
                                as $index => $photo
                            ): ?>
                                <?php
                                if (!is_array($photo)) {
                                    continue;
                                }

                                $thumbnailUrl = trim(
                                    (string) (
                                        $photo['thumbnailUrl']
                                        ?? ''
                                    )
                                );

                                $photoStatus = mb_strtoupper(
                                    trim(
                                        (string) (
                                            $photo['status']
                                            ?? ''
                                        )
                                    )
                                );

                                $ribbonClass = match ($photoStatus) {
                                    MemberPhotoModel::STATUS_APPROVED =>
                                    'ribbon-success',

                                    MemberPhotoModel::STATUS_REJECTED =>
                                    'ribbon-danger',

                                    MemberPhotoModel::STATUS_PENDING =>
                                    'ribbon-warning',

                                    default =>
                                    'ribbon-secondary',
                                };

                                $ribbonLabel = match ($photoStatus) {
                                    MemberPhotoModel::STATUS_APPROVED =>
                                    'Approved',

                                    MemberPhotoModel::STATUS_REJECTED =>
                                    'Rejected',

                                    MemberPhotoModel::STATUS_PENDING =>
                                    'Pending',

                                    default =>
                                    'Unknown',
                                };
                                ?>

                                <div
                                    class="col-8 col-sm-5
                                        col-md-4 col-lg-3
                                        flex-shrink-0">

                                    <div
                                        class="card ribbon-box
                                            border shadow-none
                                            h-100 mb-0">

                                        <div
                                            class="ribbon
                                                ribbon-shape
                                                <?= esc(
                                                    $ribbonClass,
                                                    'attr'
                                                ) ?>">

                                            <?= esc(
                                                $ribbonLabel
                                            ) ?>
                                        </div>

                                        <div
                                            class="card-body
                                                p-2 pt-5">

                                            <?php if (
                                                $thumbnailUrl !== ''
                                            ): ?>
                                                <img
                                                    src="<?= esc(
                                                                $thumbnailUrl,
                                                                'attr'
                                                            ) ?>"
                                                    alt="<?= esc(
                                                                'Member photograph '
                                                                    . ($index + 1),
                                                                'attr'
                                                            ) ?>"
                                                    class="img-thumbnail
                                                        w-100
                                                        object-fit-cover"
                                                    style="
                                                        height: 145px;
                                                        object-position:
                                                            center;
                                                    "
                                                    loading="lazy">
                                            <?php else: ?>
                                                <div
                                                    class="bg-light rounded
                                                        d-flex
                                                        align-items-center
                                                        justify-content-center"
                                                    style="height: 145px;">

                                                    <i
                                                        class="ri-image-line
                                                            fs-24
                                                            text-muted"
                                                        aria-hidden="true">
                                                    </i>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (
                                                ($photo['isPrimary']
                                                    ?? false)
                                                === true
                                            ): ?>
                                                <div
                                                    class="text-center
                                                        mt-2">

                                                    <span
                                                        class="badge
                                                            bg-primary-subtle
                                                            text-body p-2">

                                                        Primary
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Video playback -->
            <section
                class="card border border-danger
                    border-opacity-25 mb-4"
                aria-labelledby="videoPlaybackTitle">

                <div class="card-header">
                    <h2
                        id="videoPlaybackTitle"
                        class="card-title fs-16 mb-1">

                        <i
                            class="ri-video-line me-1"
                            aria-hidden="true">
                        </i>

                        Video Introduction
                    </h2>

                    <p class="text-muted fs-12 mb-0">
                        Play and review the complete recording.
                    </p>
                </div>

                <div class="card-body">
                    <div
                        class="border rounded-3
                            text-center bg-light p-4">

                        <span
                            class="avatar-lg
                                d-inline-block mb-3">

                            <span
                                class="avatar-title
                                    rounded-circle
                                    bg-primary-subtle
                                    text-primary">

                                <i
                                    class="ri-play-circle-line
                                        fs-24"
                                    aria-hidden="true">
                                </i>
                            </span>
                        </span>

                        <?php if ($playbackUrl !== ''): ?>
                            <p class="text-muted fs-13 mb-3">
                                Review the full recording before
                                making a moderation decision.
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
                        <?php else: ?>
                            <h3 class="fs-15 fw-semibold mb-1">
                                Playback unavailable
                            </h3>

                            <p class="text-muted fs-13 mb-0">
                                The processed playback video is
                                not currently available.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Technical information -->
            <section
                class="card border border-danger
                    border-opacity-25 mb-0"
                aria-labelledby="videoTechnicalTitle">

                <div class="card-header">
                    <h2
                        id="videoTechnicalTitle"
                        class="card-title fs-16 mb-0">

                        Video Information
                    </h2>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table table-nowrap
                                align-middle mb-0">

                            <tbody>
                                <tr>
                                    <th class="text-muted">
                                        Status
                                    </th>

                                    <td>
                                        <span
                                            class="badge <?= esc(
                                                                $moderationStatusPresentation['class'],
                                                                'attr'
                                                            ) ?>">

                                            <?= esc(
                                                $moderationStatusPresentation['label']
                                            ) ?>
                                        </span>
                                    </td>
                                </tr>

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
                                        <?= $videoCodec !== ''
                                            ? esc($videoCodec)
                                            : '—' ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">
                                        Audio codec
                                    </th>

                                    <td>
                                        <?= $audioCodec !== ''
                                            ? esc($audioCodec)
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
                                        <?= $consentVersion !== ''
                                            ? esc($consentVersion)
                                            : '—' ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right column -->
        <div class="col-12 col-xl-5">
            <?= view(
                'Components/Member/AdminTrustVerification',
                [
                    'trustVerification' =>
                    $trustVerification,
                ]
            ) ?>

            <!-- Moderation checklist -->
            <section
                class="card border border-warning
                    border-opacity-50 mb-4"
                aria-labelledby="videoModerationChecklistTitle">

                <div
                    class="card-header bg-transparent
                        d-flex align-items-center gap-2">

                    <span class="avatar-sm">
                        <span
                            class="avatar-title rounded-circle
                                bg-warning-subtle
                                text-warning">

                            <i
                                class="ri-shield-check-line
                                    fs-20"
                                aria-hidden="true">
                            </i>
                        </span>
                    </span>

                    <h2
                        id="videoModerationChecklistTitle"
                        class="fs-16 fw-semibold mb-0">

                        Moderation Checklist
                    </h2>
                </div>

                <div class="card-body">
                    <p class="fs-13 text-muted">
                        Confirm every requirement before approving
                        the member’s Video Introduction.
                    </p>

                    <ul class="fs-12 ps-3 mb-0">
                        <li class="mb-2">
                            One person is clearly visible and audible.
                        </li>

                        <li class="mb-2">
                            The recording is an original, respectful
                            and relevant personal introduction.
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
                            No other person’s private information
                            is disclosed.
                        </li>

                        <li class="mb-2">
                            The recording does not contain copyrighted
                            background music.
                        </li>

                        <li>
                            The member does not claim that
                            Sikhanandkaraj.com guarantees their identity.
                        </li>
                    </ul>
                </div>
            </section>

            <?php if ($canModerate): ?>
                <!-- Moderation decision -->
                <section
                    class="card border border-danger
                        border-opacity-25 mb-0"
                    aria-labelledby="videoModerationDecisionTitle">

                    <div class="card-header bg-transparent">
                        <h2
                            id="videoModerationDecisionTitle"
                            class="fs-16 fw-semibold mb-0">

                            Moderation Decision
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
                                    <span class="text-danger">*</span>
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
                                        <?= $selectedDecision
                                            === 'APPROVE'
                                            ? 'selected'
                                            : '' ?>>

                                        Approve
                                    </option>

                                    <option
                                        value="RESUBMIT"
                                        <?= $selectedDecision
                                            === 'RESUBMIT'
                                            ? 'selected'
                                            : '' ?>>

                                        Request Resubmission
                                    </option>

                                    <option
                                        value="REJECT"
                                        <?= $selectedDecision
                                            === 'REJECT'
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
                                        Provide a clear reason of at
                                        least 10 characters.
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
                                    class="btn
                                        registration-form__submit
                                        w-auto text-uppercase
                                        fw-medium"
                                    data-submit-button>

                                    <span
                                        class="registration-submit__label"
                                        data-submit-idle>

                                        <i
                                            class="ri-save-line me-1"
                                            aria-hidden="true">
                                        </i>

                                        Save Decision
                                    </span>

                                    <span
                                        class="registration-submit__loading
                                            d-none"
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
                </section>
            <?php else: ?>
                <!-- Read-only status -->
                <section
                    class="card border border-secondary
                        border-opacity-25 mb-0"
                    aria-labelledby="videoModerationStatusTitle">

                    <div class="card-header bg-transparent">
                        <h2
                            id="videoModerationStatusTitle"
                            class="fs-16 fw-semibold mb-0">

                            Moderation Decision
                        </h2>
                    </div>

                    <div class="card-body">
                        <div
                            class="alert alert-light border
                                fs-13 mb-0">

                            This submission is not pending review.
                            Its current status is

                            <strong>
                                <?= esc(
                                    $moderationStatusPresentation['label']
                                ) ?>.
                            </strong>

                            Previous decisions and reasons are
                            available in Submission History below.
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <!-- History must remain outside the review columns -->
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