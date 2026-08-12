<?php

declare(strict_types=1);

/**
 * Compact member-profile presentation.
 *
 * Used by Dashboard profile collections.
 *
 * @var array<string, mixed> $profile
 */

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$name = trim(
    (string) (
        $profile['name']
        ?? 'Member'
    )
);

if ($name === '') {
    $name = 'Member';
}

$age =
    isset($profile['age'])
    && is_numeric(
        $profile['age']
    )
    ? max(
        0,
        (int) $profile['age']
    )
    : null;

$city = trim(
    (string) (
        $profile['city']
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
        ?? '#'
    )
);

if ($profileUrl === '') {
    $profileUrl = '#';
}

$matchPercentage =
    isset(
        $profile['matchPercentage']
    )
    && is_numeric(
        $profile['matchPercentage']
    )
    ? max(
        0,
        min(
            100,
            (int)
            $profile['matchPercentage']
        )
    )
    : null;
?>

<article class="dashboard-profile-card">
    <div class="card-body p-3">

        <a
            href="<?= esc(
                        $profileUrl,
                        'attr'
                    ) ?>"
            class="d-block text-decoration-none">

            <!-- Profile thumbnail -->
            <div class="position-relative mx-auto mb-3">

                <div class="member-profile-thumbnail mx-auto">

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

            </div>

            <!-- Member identity -->
            <h3
                class="fs-14 fw-semibold
                    text-body text-center
                    text-truncate mb-1">

                <?= esc(
                    $name
                ) ?>

            </h3>

            <!-- Compact demographic summary -->
            <p
                class="text-muted
                    fs-12 text-center mb-1">

                <?php if (
                    $age !== null
                ): ?>

                    <?= esc(
                        (string) $age
                    ) ?>
                    years

                <?php endif; ?>

                <?php if (
                    $age !== null
                    && $city !== ''
                ): ?>

                    <span aria-hidden="true">
                        •
                    </span>

                <?php endif; ?>

                <?php if (
                    $city !== ''
                ): ?>

                    <?= esc(
                        $city
                    ) ?>

                <?php endif; ?>

            </p>

            <!-- Match context appears only when supplied by matchmaking. -->
            <?php if (
                $matchPercentage
                !== null
            ): ?>

                <p
                    class="text-success
                        fs-12 fw-medium
                        text-center mb-0">

                    <?= esc(
                        (string)
                        $matchPercentage
                    ) ?>%
                    preference match

                </p>

            <?php endif; ?>

        </a>

    </div>
</article>