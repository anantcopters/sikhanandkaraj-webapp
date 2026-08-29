<?php

declare(strict_types=1);

/**
 * Administrator-only member Match card.
 *
 * This deliberately does not use the normal member ProfileCard actions.
 *
 * @var array<string, mixed> $profile
 * @var int                  $memberId
 */

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$memberId = max(
    0,
    (int) (
        $memberId
        ?? 0
    )
);

$name = trim(
    (string) (
        $profile['name']
        ?? 'Member'
    )
);

$reference = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

$image = trim(
    (string) (
        $profile['image']
        ?? ''
    )
);

$profileUrl = trim(
    (string) (
        $profile['profileUrl']
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

$height = trim(
    (string) (
        $profile['height']
        ?? ''
    )
);

$location = trim(
    (string) (
        $profile['location']
        ?? ''
    )
);

$maritalStatus = trim(
    (string) (
        $profile['maritalStatus']
        ?? ''
    )
);

$professionalSummary = trim(
    (string) (
        $profile['professionalSummary']
        ?? ''
    )
);

$accountCode = mb_strtoupper(
    trim(
        (string) (
            $profile['accountCode']
            ?? ''
        )
    )
);

$partnerPreferencePercentage =
    max(
        0.0,
        min(
            100.0,
            (float) (
                $profile['partnerPreferencePercentage']
                ?? 0
            )
        )
    );

$matchScore =
    max(
        0.0,
        min(
            100.0,
            (float) (
                $profile['matchScore']
                ?? 0
            )
        )
    );
?>

<article
    class="card
        h-100
        border
        border-danger
        border-opacity-25
        shadow-sm
        overflow-hidden">

    <div class="card-body p-3">

        <div
            class="d-flex
                flex-column
                align-items-center
                text-center">

            <div class="member-profile-thumbnail">

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

            <div class="mt-1">

                <?= view(
                    'Components/Membership/PlanLogo',
                    [
                        'planCode' =>
                        $accountCode,

                        'width' =>
                        160,
                    ]
                ) ?>

            </div>

            <h3
                class="fs-17
                    fw-semibold
                    mb-1
                    mt-2">

                <?= esc(
                    $name !== ''
                        ? $name
                        : 'Member'
                ) ?>

            </h3>

            <?php if ($reference !== ''): ?>

                <div
                    class="text-muted
                        fs-13
                        mb-3">

                    <?= esc(
                        $reference
                    ) ?>

                </div>

            <?php endif; ?>

        </div>

        <div
            class="d-flex
                flex-wrap
                justify-content-center
                gap-2
                mb-3">

            <?php if ($age !== null): ?>

                <span
                    class="badge
                        bg-light
                        text-body
                        border">

                    <?= esc(
                        (string) $age
                    ) ?> yrs

                </span>

            <?php endif; ?>

            <?php if ($height !== ''): ?>

                <span
                    class="badge
                        bg-light
                        text-body
                        border">

                    <?= esc(
                        $height
                    ) ?>

                </span>

            <?php endif; ?>

            <?php if ($maritalStatus !== ''): ?>

                <span
                    class="badge
                        bg-light
                        text-body
                        border">

                    <?= esc(
                        $maritalStatus
                    ) ?>

                </span>

            <?php endif; ?>

        </div>

        <?php if ($location !== ''): ?>

            <div
                class="text-muted
                    fs-13
                    text-center
                    mb-2">

                <i
                    class="ri-map-pin-line me-1"
                    aria-hidden="true"></i>

                <?= esc(
                    $location
                ) ?>

            </div>

        <?php endif; ?>

        <?php if (
            $professionalSummary !== ''
        ): ?>

            <div
                class="text-body-secondary
                    fs-13
                    text-center
                    mb-3">

                <?= esc(
                    $professionalSummary
                ) ?>

            </div>

        <?php endif; ?>

        <!-- Admin-only scoring information -->
        <div class="row g-2 mb-3">

            <div class="col-6">

                <div
                    class="border
                        rounded
                        p-2
                        text-center
                        h-100">

                    <div
                        class="text-muted
                            fs-11
                            mb-1">

                        Partner Preference

                    </div>

                    <div
                        class="fw-semibold
                            text-primary">

                        <?= esc(
                            number_format(
                                $partnerPreferencePercentage,
                                2
                            )
                        ) ?>%

                    </div>

                </div>

            </div>

            <div class="col-6">

                <div
                    class="border
                        rounded
                        p-2
                        text-center
                        h-100">

                    <div
                        class="text-muted
                            fs-11
                            mb-1">

                        Match Score

                    </div>

                    <div
                        class="fw-semibold
                            text-success">

                        <?= esc(
                            number_format(
                                $matchScore,
                                2
                            )
                        ) ?>%

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div
        class="card-footer
            bg-transparent
            border-top
            d-flex
            gap-2">

        <a
            href="<?= esc(
                        $profileUrl,
                        'attr'
                    ) ?>"
            class="btn
                btn-sm
                btn-soft-primary
                flex-grow-1">

            <i
                class="ri-eye-line me-1"
                aria-hidden="true"></i>

            View Profile

        </a>

        <button
            type="button"
            class="btn
                btn-sm
                btn-soft-success
                flex-grow-1"
            data-match-diagnostic
            data-profile-reference="<?= esc(
                                        $reference,
                                        'attr'
                                    ) ?>"
            data-profile-name="<?= esc(
                                    $name,
                                    'attr'
                                ) ?>">

            <i
                class="ri-calculator-line me-1"
                aria-hidden="true"></i>

            Match

        </button>

    </div>

</article>