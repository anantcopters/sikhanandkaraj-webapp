<?php

declare(strict_types=1);

/**
 * Search profile-card UI variables.
 *
 * @var array<string, mixed> $profile
 */

/*
 * --------------------------------------------------------------------------
 * Normalize view-local profile variables
 * --------------------------------------------------------------------------
 *
 * Eligibility, blocking, privacy, Interest state and photo authorization are
 * already resolved before reaching this view.
 */

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
    $name =
        'Member';
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
    $profileUrl =
        '#';
}

$interestUrl =
    trim(
        (string) (
            $profile['interestUrl']
            ?? ''
        )
    );

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

$activity =
    trim(
        (string) (
            $profile['activity']
            ?? ''
        )
    );

$interestRelationship =
    isset(
        $profile['interestRelationship']
    )
    && is_array(
        $profile['interestRelationship']
    )
    ? $profile['interestRelationship']
    : [];

$interestState =
    strtoupper(
        trim(
            (string) (
                $interestRelationship['state']
                ?? 'NONE'
            )
        )
    );

$canShowInterest =
    (
        $interestRelationship['canShowInterest']
        ?? false
    ) === true;

$location =
    $city !== ''
    ? $city
    : $state;

/*
 * Relationship status is intentionally coarse.
 */
$relationshipLabel =
    match ($interestState) {
        'PENDING_SENT' =>
        'Interest Sent',

        'PENDING_RECEIVED' =>
        'Interest Received',

        'ACCEPTED_SENT',
        'ACCEPTED_RECEIVED' =>
        'Interest Accepted',

        default =>
        '',
    };
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
                                ) ?>"
                        loading="lazy">

                </div>

            </a>

            <!-- =========================================================
                 Profile summary
                 ========================================================= -->

            <div class="flex-grow-1">

                <div
                    class="d-flex align-items-start
                        justify-content-between gap-2">

                    <div>

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
                                class="text-muted fs-13 mb-2">

                                <?= esc(
                                    $reference
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <?php if (
                        $relationshipLabel !== ''
                    ): ?>

                        <span
                            class="badge bg-light
                                text-body border
                                flex-shrink-0">

                            <?= esc(
                                $relationshipLabel
                            ) ?>

                        </span>

                    <?php endif; ?>

                </div>

                <!-- =====================================================
                     Privacy-safe activity
                     ===================================================== -->

                <?php if (
                    $activity !== ''
                ): ?>

                    <div
                        class="d-flex align-items-center
                            gap-1 fs-12
                            text-success mb-2">

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

                    <p class="fs-13 mb-3">

                        <?= esc(
                            $maritalStatus
                        ) ?>

                    </p>

                <?php endif; ?>

                <!-- =====================================================
                     Profile actions
                     ===================================================== -->

                <div
                    class="d-flex flex-wrap
                        align-items-center gap-2">

                    <a
                        href="<?= esc(
                                    $profileUrl,
                                    'attr'
                                ) ?>"
                        class="btn btn-outline-primary
                            btn-sm
                            d-inline-flex
                            align-items-center
                            justify-content-center
                            gap-1">

                        <i
                            class="ri-eye-line"
                            aria-hidden="true">
                        </i>

                        View Profile

                    </a>

                    <?php if (
                        $canShowInterest
                        && $interestUrl !== ''
                    ): ?>

                        <!--
                            Use exactly the Profile View Interest route and
                            existing loader JavaScript.
                        -->
                        <form
                            method="post"
                            action="<?= esc(
                                        $interestUrl,
                                        'attr'
                                    ) ?>"
                            data-member-interest-form>

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-danger
                                    btn-sm
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-2">

                                <span
                                    class="d-inline-flex
                                        align-items-center
                                        gap-1"
                                    data-member-interest-label>

                                    <i
                                        class="ri-heart-add-line"
                                        aria-hidden="true">
                                    </i>

                                    Show Interest

                                </span>

                                <span
                                    class="d-none
                                        align-items-center
                                        gap-2"
                                    data-member-interest-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        aria-hidden="true">
                                    </span>

                                    Sending...

                                </span>

                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>
        </div>

    </div>
</article>