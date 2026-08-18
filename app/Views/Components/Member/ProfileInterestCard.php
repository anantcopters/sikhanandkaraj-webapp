<?php

declare(strict_types=1);

/**
 * Interest-context member card.
 *
 * Business decisions such as Interest visibility/status have already been
 * resolved by MemberInterestService.
 *
 * This component owns only UI presentation and the existing action forms.
 *
 * @var array<string, mixed> $profile
 * @var string               $activeDirection
 */

$profile =
    isset($profile)
    && is_array($profile)
    ? $profile
    : [];

$activeDirection =
    isset($activeDirection)
    && is_string(
        $activeDirection
    )
    ? strtolower(
        trim(
            $activeDirection
        )
    )
    : 'received';

$reference = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

$name = trim(
    (string) (
        $profile['name']
        ?? 'Member'
    )
);

if ($name === '') {
    $name = 'Member';
}

$image = trim(
    (string) (
        $profile['image']
        ?? ''
    )
);

$city = trim(
    (string) (
        $profile['city']
        ?? ''
    )
);

$location =
    trim(
        (string) (
            $profile['location']
            ?? $city
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

$status = strtoupper(
    trim(
        (string) (
            $profile['status']
            ?? 'PENDING'
        )
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

$isReceived =
    $activeDirection
    === 'received';

$canRespond =
    $isReceived
    && $status
    === 'PENDING';

/*
 * Format Interest date for presentation only.
 *
 * No business decision depends upon this formatted value.
 */
$interestDate = '';

$createdAt = trim(
    (string) (
        $profile['createdAt']
        ?? ''
    )
);

if ($createdAt !== '') {
    try {
        $interestDate = (
            new DateTimeImmutable(
                $createdAt
            )
        )->format(
            'd M y'
        );
    } catch (Throwable) {
        $interestDate = '';
    }
}

$badgeClass =
    match ($status) {
        'ACCEPTED' =>
        'bg-success-subtle text-success p-2',

        'DECLINED' =>
        'bg-danger-subtle text-danger p-2',

        default =>
        'bg-warning-subtle text-body p-2',
    };
?>

<article
    class="card border border-danger
        border-opacity-25 shadow-sm">

    <div class="card-body p-4">

        <div
            class="d-flex flex-column
                flex-sm-row gap-3">

            <!-- Member photo -->
            <a
                href="<?= esc(
                            $profileUrl,
                            'attr'
                        ) ?>"
                class="text-decoration-none
                    flex-shrink-0">

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

            </a>

            <div class="flex-grow-1">

                <!-- Member identity and Interest status -->
                <div
                    class="d-flex
                        align-items-start
                        justify-content-between
                        gap-2">

                    <div>

                        <h2
                            class="fs-18
                                fw-semibold mb-1">

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

                        </h2>

                        <?php if (
                            $reference !== ''
                        ): ?>

                            <p
                                class="text-muted
                                    fs-13 mb-2">

                                <?= esc(
                                    $reference
                                ) ?>

                            </p>

                        <?php endif; ?>

                    </div>

                    <span
                        class="badge <?= esc(
                                            $badgeClass,
                                            'attr'
                                        ) ?>">

                        <?= esc(
                            ucfirst(
                                strtolower(
                                    $status
                                )
                            )
                        ) ?>

                    </span>

                </div>

                <!-- Member summary -->
                <div
                    class="d-flex flex-wrap
                        align-items-center
                        gap-2 text-muted
                        fs-13 mb-3">

                    <?php if (
                        $age !== null
                    ): ?>

                        <span>
                            <?= esc(
                                (string) $age
                            ) ?>
                            yrs
                        </span>

                    <?php endif; ?>

                    <?php if (
                        $location !== ''
                    ): ?>

                        <span>

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

                <!-- Interest relationship message -->
                <p class="fs-13 mb-3">

                    <?php if (
                        $isReceived
                    ): ?>

                        <strong>
                            <?= esc(
                                $name
                            ) ?>
                        </strong>

                        sent you an interest

                    <?php else: ?>

                        You sent an interest to

                        <strong>
                            <?= esc(
                                $name
                            ) ?>
                        </strong>

                    <?php endif; ?>

                    <?php if (
                        $interestDate !== ''
                    ): ?>

                        <span class="text-muted">
                            ·
                            <?= esc(
                                $interestDate
                            ) ?>
                        </span>

                    <?php endif; ?>

                </p>

                <!-- Pending received Interests can be actioned here. -->
                <?php if (
                    $canRespond
                ): ?>

                    <div
                        class="d-flex
                            flex-wrap gap-2">

                        <form
                            method="post"
                            action="<?= url_to(
                                        'web.interests.received.decline',
                                        $reference
                                    ) ?>"
                            data-interest-action-form>

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn
                                    btn-outline-secondary
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-2"
                                data-interest-submit>

                                <span
                                    class="registration-submit__idle">

                                    <i
                                        class="ri-close-line"
                                        aria-hidden="true">
                                    </i>

                                    Decline

                                </span>

                                <span
                                    class="registration-submit__loading
                                        d-none">

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        aria-hidden="true">
                                    </span>

                                    Saving...

                                </span>

                            </button>

                        </form>

                        <form
                            method="post"
                            action="<?= url_to(
                                        'web.interests.received.accept',
                                        $reference
                                    ) ?>"
                            data-interest-action-form>

                            <?= csrf_field() ?>

                            <button
                                type="submit"
                                class="btn btn-danger
                                    d-inline-flex
                                    align-items-center
                                    justify-content-center
                                    gap-2"
                                data-interest-submit>

                                <span
                                    class="registration-submit__idle">

                                    <i
                                        class="ri-heart-fill"
                                        aria-hidden="true">
                                    </i>

                                    Accept Interest

                                </span>

                                <span
                                    class="registration-submit__loading
                                        d-none">

                                    <span
                                        class="spinner-border
                                            spinner-border-sm"
                                        aria-hidden="true">
                                    </span>

                                    Saving...

                                </span>

                            </button>

                        </form>

                    </div>

                <?php else: ?>

                    <a
                        href="<?= esc(
                                    $profileUrl,
                                    'attr'
                                ) ?>"
                        class="btn btn-sm
                            btn-outline-primary
                            d-inline-flex
                            align-items-center
                            justify-content-center
                            gap-2">

                        <i
                            class="ri-eye-line"
                            aria-hidden="true">
                        </i>

                        View Profile

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</article>