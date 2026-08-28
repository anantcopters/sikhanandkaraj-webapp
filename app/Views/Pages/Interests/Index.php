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

$isSent =
    $activeDirection
    === 'sent';

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

$interestActionNotice =
    isset($interestActionNotice)
    && is_array(
        $interestActionNotice
    )
    ? $interestActionNotice
    : null;
?>
<?php if (
    is_array(
        $interestActionNotice
    )
): ?>

    <div
        class="d-none"
        data-interest-action-notice
        data-notice-title="<?= esc(
                                (string) (
                                    $interestActionNotice['title']
                                    ?? 'Completed'
                                ),
                                'attr'
                            ) ?>"
        data-notice-message="<?= esc(
                                    (string) (
                                        $interestActionNotice['message']
                                        ?? 'The action was completed.'
                                    ),
                                    'attr'
                                ) ?>">
    </div>

<?php endif; ?>
<section class="py-3 py-lg-4">
    <div class="container">

        <!-- =========================================================
             Page heading
             ========================================================= -->
        <div
            class="d-flex flex-column flex-sm-row
                align-items-sm-center
                justify-content-between
                gap-2 mb-3">

            <div>
                <h1
                    class="fs-24 fw-semibold mb-1">

                    <?= esc(
                        trim(
                            $headingStatus
                                . ' '
                                . $headingDirection
                        )
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

                    <div class="row g-0">

                        <?php foreach (
                            $profiles
                            as $profile
                        ): ?>

                            <div class="col-12">

                                <?= view(
                                    'Components/Member/ProfileInterestCard',
                                    [
                                        'profile' =>
                                        $profile,

                                        'activeDirection' =>
                                        $activeDirection,
                                    ]
                                ) ?>

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