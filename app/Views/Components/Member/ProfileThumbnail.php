<?php

declare(strict_types=1);

/**
 * Compact member-profile presentation.
 *
 * Used by Dashboard profile collections.
 *
 * Clicking the thumbnail opens the shared ProfileCard component
 * inside a Bootstrap modal.
 *
 * @var array<string, mixed> $profile
 * @var string|null          $modalId
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

$referenceId = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

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

$location = trim(
    (string) (
        $profile['location']
        ?? $city
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

/*
 * Account type must come from the backend presentation contract.
 *
 * Do not provide a Free Account fallback in the view. This prevents the UI
 * from silently displaying an incorrect plan when backend plan resolution
 * is introduced later.
 */
$accountType = trim(
    (string) (
        $profile['accountType']
        ?? ''
    )
);

$accountTypeCode = mb_strtoupper(
    trim(
        (string) (
            $profile['accountCode']
            ?? ''
        )
    )
);

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
            (int) $profile['matchPercentage']
        )
    )
    : null;

$resolvedModalId =
    isset($modalId)
    && is_string($modalId)
    ? trim($modalId)
    : '';

$canOpenModal =
    $resolvedModalId !== ''
    && $referenceId !== '';
?>

<article class="dashboard-profile-card">
    <div class="card-body p-3">

        <?php if ($canOpenModal): ?>

            <button
                type="button"
                class="d-block w-100
                    border-0 bg-transparent
                    p-0 text-start"
                data-bs-toggle="modal"
                data-bs-target="#<?= esc(
                                        $resolvedModalId,
                                        'attr'
                                    ) ?>"
                aria-label="<?= esc(
                                'View '
                                    . $name
                                    . ' profile summary',
                                'attr'
                            ) ?>">

            <?php else: ?>

                <a
                    href="<?= esc(
                                $profileUrl,
                                'attr'
                            ) ?>"
                    class="d-block text-decoration-none">

                <?php endif; ?>

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

                    <?= esc($name) ?>

                </h3>

                <!-- Compact demographic summary -->
                <p
                    class="text-muted
                    fs-12 text-center mb-1">

                    <?php if ($age !== null): ?>

                        <?= esc(
                            (string) $age
                        ) ?>
                        years

                    <?php endif; ?>

                    <?php if (
                        $age !== null
                        && $location !== ''
                    ): ?>

                        <span aria-hidden="true">
                            •
                        </span>

                    <?php endif; ?>

                    <?php if ($location !== ''): ?>

                        <?= esc($location) ?>

                    <?php endif; ?>

                </p>

                <!-- Backend-supplied membership plan logo -->
                <?php if ($accountTypeCode !== ''): ?>

                    <div
                        class="d-flex
            justify-content-center
            align-items-center
            my-0">

                        <?= view(
                            'Components/Membership/PlanLogo',
                            [
                                'planCode' =>
                                $accountTypeCode,

                                'width' =>
                                180,
                            ]
                        ) ?>

                    </div>

                <?php endif; ?>

                <!-- Match context appears only when supplied by matchmaking. -->
                <?php if ($matchPercentage !== null): ?>

                    <p
                        class="text-success
                        fs-12 fw-medium
                        text-center mb-0">

                        <?= esc(
                            (string) $matchPercentage
                        ) ?>%
                        preference match

                    </p>

                <?php endif; ?>

                <?php if ($canOpenModal): ?>

            </button>

        <?php else: ?>

            </a>

        <?php endif; ?>

    </div>
</article>

<?php if ($canOpenModal): ?>

    <div
        class="modal fade"
        id="<?= esc(
                $resolvedModalId,
                'attr'
            ) ?>"
        tabindex="-1"
        aria-labelledby="<?= esc(
                                $resolvedModalId
                                    . '-title',
                                'attr'
                            ) ?>"
        aria-hidden="true"
        data-dashboard-profile-modal>

        <div
            class="modal-dialog
                modal-lg
                modal-dialog-centered
                modal-dialog-scrollable">

            <div class="modal-content">

                <div class="modal-header bg-info-subtle py-2">

                    <div>
                        <h2
                            class="modal-title fs-18"
                            id="<?= esc(
                                    $resolvedModalId
                                        . '-title',
                                    'attr'
                                ) ?>">

                            Profile Summary
                        </h2>

                        <?php if ($referenceId !== ''): ?>

                            <p class="text-muted fs-12 mb-0">

                                <?= esc($referenceId) ?>

                            </p>

                        <?php endif; ?>

                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>

                </div>

                <div class="modal-body p-3">

                    <?= view(
                        'Components/Member/ProfileCard',
                        [
                            'profile' =>
                            $profile,
                        ],
                        [
                            'saveData' =>
                            false,
                        ]
                    ) ?>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>