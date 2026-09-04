<?php

declare(strict_types=1);

/**
 * Profile sidebar cards.
 *
 * @var array<string, mixed> $overallProfileSummary
 * @var string $aboutMe
 * @var array<string, int> $aboutMeCompletion
 */
$user =
    isset($user)
    && is_array($user)
    ? $user
    : [];

$aboutMe = isset($aboutMe)
    ? trim((string) $aboutMe)
    : '';

$aboutMeCompletion = isset($aboutMeCompletion)
    && is_array($aboutMeCompletion)
    ? $aboutMeCompletion
    : [];

$hasAboutMe = $aboutMe !== '';

$aboutMeWordCount = $hasAboutMe
    ? preg_match_all(
        "/[\p{L}\p{N}]+(?:['’-][\p{L}\p{N}]+)*/u",
        $aboutMe,
        $aboutMeWords
    )
    : 0;

$summary = is_array($overallProfileSummary ?? null)
    ? $overallProfileSummary
    : [];

$hasProfilePhoto = (bool) (
    $summary['hasProfilePhoto'] ?? false
);

$profilePhotoUrl = trim(
    (string) ($summary['profilePhotoUrl'] ?? '')
);

helper(
    'member_profile'
);

$profilePhotoDisplayUrl =
    $profilePhotoUrl;

if (
    !$hasProfilePhoto
    || $profilePhotoDisplayUrl === ''
) {
    $profilePhotoDisplayUrl =
        member_profile_placeholder(
            $user['gender']
                ?? null
        );
}

$hasUploadedPhoto = (bool) (
    $summary['hasUploadedPhoto'] ?? false
);

$uploadedPhotoCount = max(
    0,
    (int) ($summary['uploadedPhotoCount'] ?? 0)
);

$approvedPhotoCount = max(
    0,
    (int) ($summary['approvedPhotoCount'] ?? 0)
);
?>

<div class="d-flex flex-column gap-3">

    <!-- About Me -->
    <section
        class="card border border-danger
        border-opacity-25 shadow-none mb-0"
        id="about-me"
        aria-labelledby="aboutMeTitle">

        <div class="card-body p-3">
            <div
                class="d-flex align-items-start
                justify-content-between gap-3 mb-3">

                <div class="d-flex align-items-start gap-3">
                    <div class="avatar-sm flex-shrink-0">
                        <span
                            class="avatar-title rounded-circle
                            bg-info-subtle text-info fs-20"
                            aria-hidden="true">

                            <i class="ri-double-quotes-l"></i>
                        </span>
                    </div>

                    <div>
                        <h2
                            class="fs-15 fw-semibold mb-1"
                            id="aboutMeTitle">

                            About Me
                        </h2>

                        <p class="text-muted fs-13 mb-0">
                            A short introduction helps members
                            understand you better.
                        </p>
                    </div>
                </div>

                <?php if ($hasAboutMe): ?>
                    <span
                        class="badge bg-success-subtle
                        text-body p-2">

                        Added
                    </span>
                <?php else: ?>
                    <span
                        class="badge bg-warning-subtle
                        text-body p-2">

                        Pending
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($hasAboutMe): ?>
                <div
                    class="border rounded bg-light-subtle
                    p-3 mb-2">

                    <p
                        class="text-body fs-13 lh-lg
                        text-break mb-0">

                        <?= nl2br(esc($aboutMe)) ?>
                    </p>
                </div>

                <div
                    class="d-flex align-items-center
                    justify-content-between gap-2 mb-3">

                    <span class="text-muted fs-12">
                        &nbsp;
                    </span>

                    <span class="text-muted fs-12">
                        <?= esc((string) $aboutMeWordCount) ?>
                        <?= $aboutMeWordCount === 1
                            ? 'word'
                            : 'words' ?>
                    </span>
                </div>
            <?php else: ?>
                <div
                    class="border border-dashed rounded
                    text-center p-3 mb-3">

                    <i
                        class="ri-chat-smile-3-line
                        text-info fs-24 d-block mb-2"
                        aria-hidden="true">
                    </i>

                    <p class="text-muted fs-13 mb-0">
                        Tell others about your personality,
                        values, interests and outlook on life.
                    </p>
                </div>
            <?php endif; ?>
            <div class="d-flex justify-content-end">
                <a
                    href="<?= url_to('web.profile.about-me') ?>"
                    class="btn btn-outline-primary">

                    <i
                        class="<?= $hasAboutMe
                                    ? 'ri-edit-line'
                                    : 'ri-add-line' ?>"
                        aria-hidden="true">
                    </i>

                    <?= $hasAboutMe
                        ? 'Edit About Me'
                        : 'Add About Me' ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Profile photo -->
    <section
        class="card border border-danger
        border-opacity-25 shadow-none mb-0"
        aria-labelledby="profilePhotoTitle">

        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">

                <div class="avatar-lg flex-shrink-0">

                    <img
                        src="<?= esc(
                                    $profilePhotoDisplayUrl,
                                    'attr'
                                ) ?>"
                        alt="Profile photo"
                        class="img-thumbnail
            object-fit-cover
            w-100"
                        loading="lazy">

                </div>

                <div class="flex-grow-1">
                    <div
                        class="d-flex align-items-start
                        justify-content-between gap-2">

                        <div>
                            <h2
                                class="fs-15 fw-semibold mb-1"
                                id="profilePhotoTitle">

                                Profile photo
                            </h2>

                            <p class="text-muted fs-13 mb-0">
                                Add clear and recent photos to improve
                                your profile visibility.
                            </p>
                        </div>

                        <?php if ($hasUploadedPhoto): ?>
                            <span
                                class="badge bg-success-subtle
                                text-body p-2">

                                Added
                            </span>
                        <?php else: ?>
                            <span
                                class="badge bg-warning-subtle
                                text-body p-2">

                                Pending
                            </span>
                        <?php endif; ?>
                    </div>

                    <div
                        class="d-flex flex-wrap align-items-center
                        gap-2 mt-3">

                        <span
                            class="badge bg-light text-body
                            border fw-medium p-2">

                            <?= esc((string) $approvedPhotoCount) ?>
                            approved
                        </span>

                        <span
                            class="text-muted fs-12">

                            <?= esc((string) $uploadedPhotoCount) ?>
                            of 5 uploaded
                        </span>
                    </div>

                    <?php if (
                        $hasUploadedPhoto
                        && !$hasProfilePhoto
                    ): ?>
                        <p class="color-pink fs-12 mb-0 mt-2">
                            Your main photo will appear here after
                            administrator approval.
                        </p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-end">
                        <a
                            href="<?= url_to('web.profile.photos') ?>"
                            class="btn btn-outline-primary mt-3">

                            <i
                                class="<?= $hasUploadedPhoto
                                            ? 'ri-image-edit-line'
                                            : 'ri-image-add-line' ?>"
                                aria-hidden="true">
                            </i>

                            <?= $hasUploadedPhoto
                                ? 'Manage photos'
                                : 'Upload photo' ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Trust and verification -->
    <?= view(
        'Components/Member/TrustVerification',
        [
            'trustVerification' =>
            $trustVerification ?? [],

            'showCard' =>
            true,
        ]
    ) ?>

</div>