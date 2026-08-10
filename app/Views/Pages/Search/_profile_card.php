<?php

declare(strict_types=1);

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
            ?? '#'
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

            <a
                href="<?= esc(
                            $profileUrl,
                            'attr'
                        ) ?>"
                class="text-decoration-none
                    flex-shrink-0">

                <?php if ($image !== ''): ?>

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
                                    ) ?>">

                    </div>

                <?php else: ?>

                    <div
                        class="member-profile-thumbnail
                            member-profile-thumbnail--fallback">

                        <span>
                            <?= esc(
                                mb_strtoupper(
                                    mb_substr(
                                        $name,
                                        0,
                                        1
                                    )
                                )
                            ) ?>
                        </span>

                    </div>

                <?php endif; ?>

            </a>

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

                        <?= esc($name) ?>

                    </a>

                </h3>

                <div
                    class="text-muted
                        fs-13 mb-2">

                    <?= esc(
                        $reference
                    ) ?>

                </div>

                <div
                    class="d-flex flex-wrap
                        gap-2 fs-13
                        text-muted mb-3">

                    <?php if (
                        is_numeric(
                            $profile['age']
                                ?? null
                        )
                    ): ?>

                        <span>
                            <?= esc(
                                (string)
                                $profile['age']
                            ) ?>
                            yrs
                        </span>

                    <?php endif; ?>

                    <?php if (
                        trim(
                            (string) (
                                $profile['height']
                                ?? ''
                            )
                        ) !== ''
                    ): ?>

                        <span>
                            ·
                            <?= esc(
                                (string)
                                $profile['height']
                            ) ?>
                        </span>

                    <?php endif; ?>

                    <?php if (
                        trim(
                            (string) (
                                $profile['city']
                                ?? ''
                            )
                        ) !== ''
                    ): ?>

                        <span>
                            ·
                            <i
                                class="ri-map-pin-line"
                                aria-hidden="true">
                            </i>

                            <?= esc(
                                (string)
                                $profile['city']
                            ) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <?php if (
                    trim(
                        (string) (
                            $profile['maritalStatus']
                            ?? ''
                        )
                    ) !== ''
                ): ?>

                    <p class="fs-13 mb-3">
                        <?= esc(
                            (string)
                            $profile['maritalStatus']
                        ) ?>
                    </p>

                <?php endif; ?>

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