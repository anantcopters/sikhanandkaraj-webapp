<?php

declare(strict_types=1);

/**
 * @var int $minimumDurationSeconds
 * @var int $maximumDurationSeconds
 * @var int $maximumUploadSizeKb
 * @var string $consentVersion
 * @var array<string, mixed>|null $videoIntroduction
 * @var array<string, mixed>|null $formAlert
 */

$minimumDurationSeconds = max(
    1,
    (int) ($minimumDurationSeconds ?? 15)
);

$maximumDurationSeconds = max(
    $minimumDurationSeconds,
    (int) ($maximumDurationSeconds ?? 30)
);

$maximumUploadSizeKb = max(
    1,
    (int) ($maximumUploadSizeKb ?? 40960)
);

$consentVersion = trim(
    (string) ($consentVersion ?? '')
);

$this->extend('Layouts/Main');

$this->section('content');
?>

<section class="py-3 py-lg-3">
    <div class="container">
        <?= view(
            'Components/Alerts/FormAlert',
            [
                'alert' => $formAlert ?? null,
            ]
        ) ?>

        <div
            class="d-flex align-items-center
                justify-content-between gap-3 mb-4">

            <div>
                <h1 class="fs-24 fw-semibold mb-1">
                    Record Video Introduction
                </h1>

                <p class="text-muted mb-0">
                    Record a clear
                    <?= esc(
                        (string) $minimumDurationSeconds
                    ) ?>–<?= esc(
                                (string) $maximumDurationSeconds
                            ) ?>
                    second personal introduction.
                </p>
            </div>

            <a
                href="<?= route_to(
                            'web.account.settings.section',
                            'video-introduction'
                        ) ?>"
                class="btn btn-outline-secondary">

                <i
                    class="ri-arrow-left-line me-1"
                    aria-hidden="true">
                </i>

                Back
            </a>
        </div>

        <form
            method="post"
            enctype="multipart/form-data"
            action="<?= route_to(
                        'web.video-introduction.submit'
                    ) ?>"
            data-video-form
            data-submit-loader>

            <?= csrf_field() ?>

            <input
                type="file"
                name="video_introduction"
                class="d-none"
                accept="video/webm,video/mp4,video/quicktime"
                data-recorded-file
                required>

            <input
                type="hidden"
                name="consent_version"
                value="<?= esc(
                            $consentVersion,
                            'attr'
                        ) ?>">

            <div class="row g-4">
                <div class="col-12 col-xl-5">
                    <div class="card border border-warning
                            border-opacity-50 shadow-sm mb-3">
                        <div
                            class="card-header bg-transparent
                                d-flex align-items-center gap-2">

                            <span
                                class="avatar-sm flex-shrink-0">

                                <span
                                    class="avatar-title rounded-circle
                                        bg-primary-subtle text-primary fs-20">

                                    <i
                                        class="ri-question-answer-line"
                                        aria-hidden="true">
                                    </i>
                                </span>
                            </span>

                            <h2 class="fs-16 fw-semibold mb-0">
                                Optional prompts
                            </h2>
                        </div>

                        <div class="card-body p-3">
                            <p class="fs-13 color-pink mb-3">
                                Use these ideas to help structure your
                                introduction. Speak naturally—you do not
                                need to cover every prompt.
                            </p>

                            <ul class="fs-13 mb-0 ps-3">
                                <li class="mb-2">
                                    First name and city
                                </li>

                                <li class="mb-2">
                                    Education or profession
                                </li>

                                <li class="mb-2">
                                    Hobbies and interests
                                </li>

                                <li class="mb-2">
                                    A few words about family values
                                </li>

                                <li>
                                    Qualities expected in a life partner
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div
                        class="card border border-warning
                            border-opacity-50 shadow-sm">

                        <div
                            class="card-header bg-transparent
                                d-flex align-items-center gap-2">

                            <span
                                class="avatar-sm flex-shrink-0">

                                <span
                                    class="avatar-title rounded-circle
                                        bg-warning-subtle text-warning fs-20">

                                    <i
                                        class="ri-shield-check-line"
                                        aria-hidden="true">
                                    </i>
                                </span>
                            </span>

                            <h2 class="fs-16 fw-semibold mb-0">
                                Instructions and privacy consent
                            </h2>
                        </div>

                        <div class="card-body p-3">
                            <p class="fs-13 text-danger mb-3">
                                Please read these instructions carefully.
                                Your consent confirms that your recording
                                follows the privacy and moderation rules.
                            </p>

                            <p class="fs-13">
                                Introduce yourself, your interests,
                                education or profession and what you are
                                looking for in a life partner. Do not
                                share your phone number, email, address
                                or social-media details.
                            </p>

                            <ul class="fs-12 text-muted ps-3">
                                <li class="mb-2">
                                    The video must be an original,
                                    respectful and relevant personal
                                    introduction.
                                </li>

                                <li class="mb-2">
                                    Do not speak or display a phone
                                    number, email, address or
                                    social-media handle.
                                </li>

                                <li class="mb-2">
                                    Do not include offensive, misleading
                                    or promotional content.
                                </li>

                                <li class="mb-2">
                                    Do not disclose another person's
                                    private information.
                                </li>

                                <li class="mb-2">
                                    Do not use copyrighted background
                                    music.
                                </li>

                                <li class="mb-2">
                                    Do not claim that Sikhanandkaraj
                                    guarantees your identity.
                                </li>

                                <li>
                                    The video cannot be deleted or
                                    replaced for seven days, but it may
                                    be hidden.
                                </li>
                            </ul>

                            <hr>

                            <div class="form-check mb-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    value="1"
                                    id="privacyConsent"
                                    name="privacy_consent"
                                    data-privacy-consent
                                    required>

                                <label
                                    class="form-check-label fs-13"
                                    for="privacyConsent">

                                    I have read and agree to the Video
                                    Introduction guidelines, privacy
                                    conditions and seven-day restriction.
                                </label>
                            </div>

                            <p class="color-pink fs-12 mb-0">
                                Accept the consent before enabling the
                                camera and microphone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div
                        class="card border border-danger
                            border-opacity-25 shadow-sm">

                        <div
                            class="card-header bg-transparent
                                d-flex align-items-center gap-2">

                            <span
                                class="avatar-sm flex-shrink-0">

                                <span
                                    class="avatar-title rounded-circle
                                        bg-danger-subtle text-danger fs-20">

                                    <i
                                        class="ri-video-line"
                                        aria-hidden="true">
                                    </i>
                                </span>
                            </span>

                            <div>
                                <h2 class="fs-16 fw-semibold mb-0">
                                    Video recorder
                                </h2>

                                <p class="text-muted fs-12 mb-0">
                                    Keep your face visible and speak
                                    clearly in a quiet place.
                                </p>
                            </div>
                        </div>

                        <div class="card-body p-3 p-lg-4">
                            <div
                                class="ratio ratio-16x9 bg-dark
        rounded overflow-hidden mb-3">

                                <video
                                    class="w-100 h-100 object-fit-cover"
                                    muted
                                    playsinline
                                    data-camera-preview>
                                </video>

                                <video
                                    class="w-100 h-100 object-fit-cover d-none"
                                    controls
                                    playsinline
                                    preload="metadata"
                                    data-recording-preview>
                                </video>
                            </div>

                            <div
                                class="d-flex align-items-center
                                    justify-content-between gap-3 mb-3">

                                <span
                                    class="badge bg-light text-body
                                        border fs-14 p-2"
                                    data-countdown>

                                    <?= esc(
                                        (string) $maximumDurationSeconds
                                    ) ?>
                                    seconds remaining
                                </span>

                                <span
                                    class="text-danger fs-14"
                                    data-recorder-status>

                                    Camera not started
                                </span>
                            </div>

                            <div
                                class="alert alert-danger d-none"
                                role="alert"
                                data-recorder-error>
                            </div>

                            <div
                                class="d-flex flex-wrap
                                    justify-content-end gap-2">

                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    data-enable-camera
                                    disabled>

                                    <i
                                        class="ri-camera-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Enable Camera
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-primary d-none"
                                    data-start-recording>

                                    <i
                                        class="ri-record-circle-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Start Recording
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-danger d-none"
                                    data-stop-recording
                                    disabled>

                                    <i
                                        class="ri-stop-circle-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Stop Recording
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger d-none"
                                    data-retake>

                                    <i
                                        class="ri-restart-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Retake
                                </button>
                            </div>

                            <div
                                class="d-none mt-4"
                                data-submit-review>

                                <div
                                    class="alert alert-success
                                        d-flex align-items-start gap-2">

                                    <i
                                        class="ri-checkbox-circle-line
                                            fs-18"
                                        aria-hidden="true">
                                    </i>

                                    <div>
                                        <h3
                                            class="fs-14 fw-semibold
                                                text-success mb-1">

                                            Recording ready
                                        </h3>

                                        <p class="fs-12 mb-0">
                                            Preview the complete recording.
                                            You can retake it or submit it
                                            for admin review.
                                        </p>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button
                                        type="submit"
                                        class="btn registration-form__submit
            fs-14 fw-semibold text-uppercase w-50"
                                        data-submit-button
                                        disabled>

                                        <span
                                            class="registration-submit__label"
                                            data-submit-idle>

                                            <i
                                                class="ri-upload-cloud-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            Submit for Review
                                        </span>

                                        <span
                                            class="registration-submit__loading d-none"
                                            data-submit-loading
                                            aria-hidden="true">

                                            <span
                                                class="spinner-border spinner-border-sm"
                                                role="status"
                                                aria-hidden="true">
                                            </span>

                                            Submitting...
                                        </span>
                                    </button>
                                </div>

                                <p
                                    class="color-pink fs-12
                                        text-center mt-3 mb-0">

                                    Maximum upload size:
                                    <?= esc(
                                        number_format(
                                            $maximumUploadSizeKb / 1024,
                                            0
                                        )
                                    ) ?>
                                    MB. Processing continues after upload.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<div
    data-video-recorder-config
    data-min-seconds="<?= esc(
                            (string) $minimumDurationSeconds,
                            'attr'
                        ) ?>"
    data-max-seconds="<?= esc(
                            (string) $maximumDurationSeconds,
                            'attr'
                        ) ?>"
    data-max-size-bytes="<?= esc(
                                (string) (
                                    $maximumUploadSizeKb * 1024
                                ),
                                'attr'
                            ) ?>">
</div>

<?php $this->endSection(); ?>