<?php

declare(strict_types=1);

/**
 * Search profile-card UI variables.
 *
 * @var array<string, mixed> $profile
 */

/*
 * --------------------------------------------------------------------------
 * Normalize view-local profile values
 * --------------------------------------------------------------------------
 *
 * Only presentation normalization belongs here. Eligibility, privacy,
 * blocking and photo authorization have already been resolved by the
 * Search service.
 */

/**
 * Search profile-card UI variables.
 *
 * @var array<string, mixed> $profile
 */

$activity =
    trim(
        (string) (
            $profile['activity']
            ?? ''
        )
    );

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$name =
    trim(
        (string) (
            $profile['name']
            ?? 'Member'
        )
    );

if ($name === '') {
    $name = 'Member';
}

$reference =
    trim(
        (string) (
            $profile['referenceId']
            ?? ''
        )
    );

$image =
    trim(
        (string) (
            $profile['image']
            ?? ''
        )
    );

$profileUrl =
    trim(
        (string) (
            $profile['profileUrl']
            ?? ''
        )
    );

if ($profileUrl === '') {
    $profileUrl = '#';
}

$age =
    is_numeric(
        $profile['age']
            ?? null
    )
    ? max(
        0,
        (int) $profile['age']
    )
    : null;

$height =
    trim(
        (string) (
            $profile['height']
            ?? ''
        )
    );

$city =
    trim(
        (string) (
            $profile['city']
            ?? ''
        )
    );

$state =
    trim(
        (string) (
            $profile['state']
            ?? ''
        )
    );

$maritalStatus =
    trim(
        (string) (
            $profile['maritalStatus']
            ?? ''
        )
    );

$location =
    $city !== ''
    ? $city
    : $state;

/*
 * Use the same fallback-initial approach already used by member-facing
 * discovery cards when no viewer-authorized photo URL is available.
 */
$initial =
    mb_strtoupper(
        mb_substr(
            $name,
            0,
            1
        )
    );
?>

<article
    class="card h-100 border border-danger
        border-opacity-25 shadow-sm">

    <div class="card-body p-3 p-md-4">

        <div
            class="d-flex flex-column
                flex-sm-row gap-3">

            <!-- =========================================================
                 Profile photo
                 ========================================================= -->

            <a
                href="<?= esc(
                            $profileUrl,
                            'attr'
                        ) ?>"
                class="text-decoration-none
                    flex-shrink-0">

                <?php if ($image !== ''): ?>

                    <!-- Viewer-authorized approved primary photo. -->
                    <div
                        class="member-profile-thumbnail">

                        <img
                            src="<?= esc(
                                        $image,
                                        'attr'
                                    ) ?>"
                            alt="<?= esc(
                                        $name
                                            . ' profile photo',
                                        'attr'
                                    ) ?>">

                    </div>

                <?php else: ?>

                    <!--
                        Photo is absent or not authorized for the viewer.
                        Do not distinguish those privacy states in the UI.
                    -->
                    <div
                        class="member-profile-thumbnail
                            member-profile-thumbnail--fallback"
                        aria-label="<?= esc(
                                        $name,
                                        'attr'
                                    ) ?>">

                        <span>
                            <?= esc(
                                $initial
                            ) ?>
                        </span>

                    </div>

                <?php endif; ?>

            </a>

            <!-- =========================================================
                 Profile summary
                 ========================================================= -->

            <div class="flex-grow-1">

                <h3
                    class="fs-18 fw-semibold mb-1">

                    <a
                        href="<?= esc(
                                    $profileUrl,
                                    'attr'
                                ) ?>"
                        class="text-body
                            text-decoration-none">

                        <?= esc(
                            $name
                        ) ?>

                    </a>

                </h3>

                <?php if (
                    $reference !== ''
                ): ?>

                    <div
                        class="text-muted
                            fs-13 mb-2">

                        <?= esc(
                            $reference
                        ) ?>

                    </div>

                <?php endif; ?>

                <?php if (
                    $activity !== ''
                ): ?>

                    <!--
                        Exact login timestamp is intentionally never exposed.
                    -->
                    <div
                        class="d-flex align-items-center
            gap-1 fs-12 text-success mb-2">

                        <i
                            class="ri-checkbox-blank-circle-fill"
                            aria-hidden="true">
                        </i>

                        <span>
                            <?= esc(
                                $activity
                            ) ?>
                        </span>

                    </div>

                <?php endif; ?>

                <!-- =====================================================
                     Basic profile summary
                     ===================================================== -->

                <div
                    class="d-flex flex-wrap
                        gap-2 fs-13
                        text-muted mb-3">

                    <?php if (
                        $age !== null
                        && $age > 0
                    ): ?>

                        <span>
                            <?= esc(
                                (string) $age
                            ) ?>
                            yrs
                        </span>

                    <?php endif; ?>

                    <?php if (
                        $height !== ''
                    ): ?>

                        <span>
                            <?= $age !== null
                                ? '· '
                                : '' ?>

                            <?= esc(
                                $height
                            ) ?>
                        </span>

                    <?php endif; ?>

                    <?php if (
                        $location !== ''
                    ): ?>

                        <span>
                            ·

                            <i
                                class="ri-map-pin-line"
                                aria-hidden="true">
                            </i>

                            <?= esc(
                                $location
                            ) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <?php if (
                    $maritalStatus !== ''
                ): ?>

                    <p
                        class="fs-13 mb-3">

                        <?= esc(
                            $maritalStatus
                        ) ?>

                    </p>

                <?php endif; ?>

                <!-- =====================================================
                     Profile action
                     ===================================================== -->

                <a
                    href="<?= esc(
                                $profileUrl,
                                'attr'
                            ) ?>"
                    class="btn btn-outline-primary
                        btn-sm">

                    View Profile

                    <i
                        class="ri-arrow-right-line ms-1"
                        aria-hidden="true">
                    </i>

                </a>

            </div>
        </div>

    </div>
</article>