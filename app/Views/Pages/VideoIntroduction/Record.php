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

<section class="py-3 py-lg-4">
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
                    <?= esc((string) $minimumDurationSeconds) ?>–<?= esc((string) $maximumDurationSeconds) ?>
                    second personal introduction.
                </p>
            </div>

            <a
                href="<?= route_to(
                            'web.account.settings.section',
                            'video-introduction'
                        ) ?>"
                class="btn btn-outline-secondary">

                Back
            </a>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-3 p-lg-4">
                        <div
                            class="ratio ratio-4x3 bg-dark
                                rounded overflow-hidden mb-3">

                            <video
                                class="w-100 h-100"
                                muted
                                playsinline
                                data-camera-preview>
                            </video>

                            <video
                                class="w-100 h-100 d-none"
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
                                class="text-muted fs-13"
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
                                class="btn btn-danger d-none"
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
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-3">
                        <h2 class="fs-16 fw-semibold">
                            Optional prompts
                        </h2>

                        <ul class="fs-13 mb-0 ps-3">
                            <li>First name and city</li>
                            <li>Education or profession</li>
                            <li>Hobbies and interests</li>
                            <li>
                                A few words about family values
                            </li>
                            <li>
                                Qualities expected in a life partner
                            </li>
                        </ul>
                    </div>
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

                    <div
                        class="card border border-warning
                            border-opacity-50 shadow-sm">

                        <div class="card-body p-3">
                            <h2 class="fs-16 fw-semibold">
                                Instructions and privacy consent
                            </h2>

                            <p class="fs-13">
                                Introduce yourself, your interests,
                                education/profession and what you seek
                                in a life partner. Do not share phone,
                                email, address or social-media details.
                            </p>

                            <ul class="fs-12 text-muted ps-3">
                                <li>
                                    Only one person should be clearly
                                    visible and audible.
                                </li>

                                <li>
                                    Content must be original,
                                    respectful and relevant.
                                </li>

                                <li>
                                    No offensive, misleading or
                                    promotional content.
                                </li>

                                <li>
                                    No other person's private
                                    information.
                                </li>

                                <li>
                                    No copyrighted background music.
                                </li>

                                <li>
                                    Do not claim Sikhanandkaraj
                                    guarantees your identity.
                                </li>

                                <li>
                                    The video cannot be deleted or
                                    replaced for seven days, but it
                                    may be hidden.
                                </li>
                            </ul>

                            <div class="form-check mb-3">
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

                            <p class="text-muted fs-12">
                                Accept the consent before enabling the
                                camera and microphone.
                            </p>

                            <input
                                type="hidden"
                                name="consent_version"
                                value="<?= esc(
                                            $consentVersion,
                                            'attr'
                                        ) ?>">

                            <button
                                type="submit"
                                class="btn btn-danger w-100"
                                data-submit-button
                                disabled>

                                <span data-submit-idle>
                                    <i
                                        class="ri-upload-cloud-line me-1"
                                        aria-hidden="true">
                                    </i>

                                    Submit for Review
                                </span>

                                <span
                                    class="d-none"
                                    data-submit-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm me-1">
                                    </span>

                                    Uploading...
                                </span>
                            </button>

                            <p class="text-muted fs-11 mt-2 mb-0">
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
                </form>
            </div>
        </div>
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
                                (string) ($maximumUploadSizeKb * 1024),
                                'attr'
                            ) ?>">
</div>

<?php $this->endSection(); ?>