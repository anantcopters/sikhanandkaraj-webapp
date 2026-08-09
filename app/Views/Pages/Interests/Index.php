<?php

declare(strict_types=1);

/**
 * @var string $activeDirection
 * @var string $activeFilter
 * @var array<string, array<string, int>> $counts
 * @var list<array<string, mixed>> $profiles
 */

$this->extend(
    'Layouts/Main'
);

$this->section(
    'content'
);

$receivedCounts =
    $counts['received']
    ?? [
        'all' => 0,
        'pending' => 0,
        'accepted' => 0,
        'declined' => 0,
    ];

$sentCounts =
    $counts['sent']
    ?? [
        'all' => 0,
        'pending' => 0,
        'accepted' => 0,
        'declined' => 0,
    ];

$isReceived =
    $activeDirection
    === 'received';

$headingDirection =
    $isReceived
    ? 'Interests Received'
    : 'Interests Sent';

$headingStatus =
    match ($activeFilter) {
        'pending' =>
        'Pending',

        'accepted' =>
        'Accepted',

        'declined' =>
        'Declined',

        default =>
        'All',
    };

$emptyMessage =
    $isReceived
    ? 'No received interests are available for this filter.'
    : 'No sent interests are available for this filter.';

$interestUrl =
    static function (
        string $direction,
        string $status
    ): string {
        return route_to(
            'web.interests'
        )
            . '?direction='
            . rawurlencode(
                $direction
            )
            . '&status='
            . rawurlencode(
                $status
            );
    };
?>

<section class="py-3 py-lg-4">
    <div class="container">

        <div class="row g-4">

            <!-- =========================================================
                 Left navigation
                 ========================================================= -->
            <aside class="col-12 col-lg-4 col-xl-3">

                <div
                    class="card border border-danger border-opacity-25 shadow-sm">

                    <div class="card-body p-4">

                        <h2
                            class="fs-16 fw-semibold mb-3">

                            Interests Received
                        </h2>

                        <nav
                            class="nav flex-column gap-1"
                            aria-label="Interests received">

                            <?php
                            foreach (
                                [
                                    'all' =>
                                    'All',

                                    'pending' =>
                                    'Pending',

                                    'accepted' =>
                                    'Accepted',

                                    'declined' =>
                                    'Declined',
                                ]
                                as $status => $label
                            ):
                                $isActive =
                                    $activeDirection
                                    === 'received'
                                    && $activeFilter
                                    === $status;
                            ?>

                                <a
                                    href="<?= esc(
                                                $interestUrl(
                                                    'received',
                                                    $status
                                                ),
                                                'attr'
                                            ) ?>"
                                    class="nav-link px-0 d-flex align-items-center justify-content-between gap-3 <?= $isActive
                                                                                                                        ? 'text-primary fw-semibold'
                                                                                                                        : 'text-body'
                                                                                                                    ?>">

                                    <span>
                                        <?= esc(
                                            $label
                                        ) ?>
                                    </span>

                                    <span
                                        class="badge bg-light text-body border">

                                        <?= esc(
                                            (string) (
                                                $receivedCounts[$status]
                                                ?? 0
                                            )
                                        ) ?>
                                    </span>
                                </a>

                            <?php endforeach; ?>

                        </nav>

                        <hr class="my-4">

                        <h2
                            class="fs-16 fw-semibold mb-3">

                            Interests Sent
                        </h2>

                        <nav
                            class="nav flex-column gap-1"
                            aria-label="Interests sent">

                            <?php
                            foreach (
                                [
                                    'all' =>
                                    'All',

                                    'pending' =>
                                    'Pending',

                                    'accepted' =>
                                    'Accepted',

                                    'declined' =>
                                    'Declined',
                                ]
                                as $status => $label
                            ):
                                $isActive =
                                    $activeDirection
                                    === 'sent'
                                    && $activeFilter
                                    === $status;
                            ?>

                                <a
                                    href="<?= esc(
                                                $interestUrl(
                                                    'sent',
                                                    $status
                                                ),
                                                'attr'
                                            ) ?>"
                                    class="nav-link px-0 d-flex align-items-center justify-content-between gap-3 <?= $isActive
                                                                                                                        ? 'text-primary fw-semibold'
                                                                                                                        : 'text-body'
                                                                                                                    ?>">

                                    <span>
                                        <?= esc(
                                            $label
                                        ) ?>
                                    </span>

                                    <span
                                        class="badge bg-light text-body border">

                                        <?= esc(
                                            (string) (
                                                $sentCounts[$status]
                                                ?? 0
                                            )
                                        ) ?>
                                    </span>
                                </a>

                            <?php endforeach; ?>

                        </nav>

                    </div>
                </div>

            </aside>

            <!-- =========================================================
                 Profiles
                 ========================================================= -->
            <div class="col-12 col-lg-8 col-xl-9">

                <div
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">

                    <div>
                        <h1
                            class="fs-24 fw-semibold mb-1">

                            <?= esc(
                                $headingStatus
                                    . ' '
                                    . $headingDirection
                            ) ?>
                        </h1>

                        <p
                            class="text-muted mb-0">

                            <?php if ($isReceived): ?>

                                Manage members who have
                                shown interest in your profile.

                            <?php else: ?>

                                Review interests you have
                                sent to other members.

                            <?php endif; ?>

                        </p>
                    </div>

                    <span
                        class="badge bg-primary text-white border p-2 fs-12">

                        <?= esc(
                            (string)
                            count(
                                $profiles
                            )
                        ) ?>
                        profiles
                    </span>

                </div>

                <?php if ($profiles === []): ?>

                    <div
                        class="card border border-danger border-opacity-25 shadow-sm">

                        <div
                            class="card-body p-5 text-center">

                            <i
                                class="ri-heart-line fs-36 text-muted"
                                aria-hidden="true">
                            </i>

                            <h2
                                class="fs-18 fw-semibold mt-3 mb-2">

                                No interests found
                            </h2>

                            <p
                                class="text-muted mb-0">

                                <?= esc(
                                    $emptyMessage
                                ) ?>
                            </p>

                        </div>
                    </div>

                <?php else: ?>

                    <!-- Exactly two profiles per row on XL desktop. -->
                    <div class="row g-0">

                        <?php foreach (
                            $profiles
                            as $profile
                        ): ?>

                            <?php
                            $reference =
                                trim(
                                    (string) (
                                        $profile['referenceId']
                                        ?? ''
                                    )
                                );

                            $name =
                                trim(
                                    (string) (
                                        $profile['name']
                                        ?? 'Member'
                                    )
                                );

                            $image =
                                trim(
                                    (string) (
                                        $profile['image']
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

                            $status =
                                strtoupper(
                                    trim(
                                        (string) (
                                            $profile['status']
                                            ?? 'PENDING'
                                        )
                                    )
                                );

                            $profileUrl =
                                trim(
                                    (string) (
                                        $profile['profileUrl']
                                        ?? '#'
                                    )
                                );

                            $canRespond =
                                $activeDirection
                                === 'received'
                                && $status
                                === 'PENDING';

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
                            ?>

                            <div class="col-12">

                                <article
                                    class="card border border-danger border-opacity-25 shadow-sm">

                                    <div class="card-body p-4">

                                        <div
                                            class="d-flex flex-column flex-sm-row gap-3">

                                            <a
                                                href="<?= esc(
                                                            $profileUrl,
                                                            'attr'
                                                        ) ?>"
                                                class="text-decoration-none flex-shrink-0">

                                                <?php if (
                                                    $image !== ''
                                                ): ?>

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

                                                    <div
                                                        class="member-profile-thumbnail member-profile-thumbnail--fallback"
                                                        aria-label="<?= esc(
                                                                        $name,
                                                                        'attr'
                                                                    ) ?>">

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

                                                <div
                                                    class="d-flex align-items-start justify-content-between gap-2">

                                                    <div>

                                                        <h2
                                                            class="fs-18 fw-semibold mb-1">

                                                            <a
                                                                href="<?= esc(
                                                                            $profileUrl,
                                                                            'attr'
                                                                        ) ?>"
                                                                class="text-body text-decoration-none">

                                                                <?= esc(
                                                                    $name
                                                                ) ?>
                                                            </a>
                                                        </h2>

                                                        <p
                                                            class="text-muted fs-13 mb-2">

                                                            <?= esc(
                                                                $reference
                                                            ) ?>
                                                        </p>

                                                    </div>

                                                    <?php
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

                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-2 text-muted fs-13 mb-3">

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
                                                        $city !== ''
                                                    ): ?>

                                                        <span>
                                                            <i
                                                                class="ri-map-pin-line"
                                                                aria-hidden="true">
                                                            </i>

                                                            <?= esc(
                                                                $city
                                                            ) ?>
                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                                <?php if (
                                                    $activeDirection
                                                    === 'received'
                                                ): ?>

                                                    <p class="fs-13 mb-3">

                                                        <strong>
                                                            <?= esc($name) ?>
                                                        </strong>

                                                        sent you an interest

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

                                                <?php else: ?>

                                                    <p class="fs-13 mb-3">

                                                        You sent an interest to

                                                        <strong>
                                                            <?= esc($name) ?>
                                                        </strong>

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

                                                <?php endif; ?>

                                                <?php if (
                                                    $canRespond
                                                ): ?>

                                                    <div
                                                        class="d-flex flex-wrap gap-2">

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
                                                                class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-2"
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
                                                                    class="registration-submit__loading d-none">

                                                                    <span
                                                                        class="spinner-border spinner-border-sm"
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
                                                                class="btn btn-danger d-inline-flex align-items-center justify-content-center gap-2"
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
                                                                    class="registration-submit__loading d-none">

                                                                    <span
                                                                        class="spinner-border spinner-border-sm"
                                                                        aria-hidden="true">
                                                                    </span>

                                                                    Saving...
                                                                </span>

                                                            </button>
                                                        </form>

                                                    </div>

                                                <?php else: ?>
                                                    <a href="<?= esc(
                                                                    $profileUrl,
                                                                    'attr'
                                                                ) ?>" class="btn btn-sm btn-outline-primary
                        d-inline-flex
                        align-items-center
                        justify-content-center
                        gap-2">

                                                        <i class="ri-eye-line" aria-hidden="true">
                                                        </i>

                                                        View Profile
                                                    </a>


                                                <?php endif; ?>

                                            </div>
                                        </div>

                                    </div>
                                </article>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php
$this->endSection();
?>