<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $video
 * @var string $playbackUrl
 * @var string $posterUrl
 * @var list<array<string, mixed>> $history
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

$this->extend('Admin/Layouts/Main');

$this->section('content');
?>

<section class="py-3 py-lg-4">
    <div class="container-fluid px-3 px-lg-4">
        <?= view(
            'Components/Alerts/FormAlert',
            [
                'alert' => $formAlert ?? null,
            ]
        ) ?>

        <div
            class="d-flex justify-content-between
                align-items-start gap-3 mb-4">

            <div>
                <h1 class="fs-24 fw-semibold mb-1">
                    Review Video Introduction
                </h1>

                <p class="text-muted mb-0">
                    Check the complete moderation policy
                    before deciding.
                </p>
            </div>

            <a
                href="<?= route_to(
                            'admin.members.video-introductions'
                        ) ?>"
                class="btn btn-outline-secondary">

                Back
            </a>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
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

                        <dl class="row fs-13 mt-3 mb-0">
                            <dt class="col-5">
                                Duration
                            </dt>

                            <dd class="col-7">
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
                            </dd>

                            <dt class="col-5">
                                Video / Audio
                            </dt>

                            <dd class="col-7">
                                <?= esc(
                                    (string) (
                                        $video['video_codec']
                                        ?? ''
                                    )
                                ) ?>
                                /
                                <?= esc(
                                    (string) (
                                        $video['audio_codec']
                                        ?? ''
                                    )
                                ) ?>
                            </dd>

                            <dt class="col-5">
                                Consent version
                            </dt>

                            <dd class="col-7">
                                <?= esc(
                                    (string) (
                                        $video['consent_version']
                                        ?? ''
                                    )
                                ) ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div
                    class="card border border-danger
                        border-opacity-25 shadow-sm">

                    <div class="card-body p-3">
                        <h2 class="fs-16 fw-semibold">
                            Moderation checklist
                        </h2>

                        <ul class="fs-12 ps-3">
                            <li>
                                One person is clearly visible
                                and audible.
                            </li>

                            <li>
                                Original, respectful and
                                relevant introduction.
                            </li>

                            <li>
                                No phone, email, address or
                                social-media details.
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
                                No claim that SikhanAndKaraj
                                guarantees identity.
                            </li>
                        </ul>

                        <form
                            method="post"
                            action="<?= route_to(
                                        'admin.members.video-introductions.moderate',
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
                                    required>

                                    <option value="">
                                        Select
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
                            </div>

                            <div class="mb-3">
                                <label
                                    for="reason"
                                    class="form-label">

                                    Reason
                                    <span class="text-muted">
                                        (required for
                                        reject/resubmission)
                                    </span>
                                </label>

                                <textarea
                                    id="reason"
                                    name="reason"
                                    class="form-control"
                                    minlength="10"
                                    maxlength="500"
                                    rows="4"></textarea>

                                <div class="invalid-feedback">
                                    Use 10–500 characters.
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
</section>

<?php $this->endSection(); ?>