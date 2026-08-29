<?php

declare(strict_types=1);

/**
 * Administrator Partner Preference Match details.
 *
 * @var string               $modalId
 * @var array<string, mixed> $profile
 */

$modalId = trim(
    (string) (
        $modalId
        ?? ''
    )
);

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

$reference = trim(
    (string) (
        $profile['referenceId']
        ?? ''
    )
);

$match =
    isset(
        $profile['partnerPreferenceMatch']
    )
    && is_array(
        $profile['partnerPreferenceMatch']
    )
    ? $profile['partnerPreferenceMatch']
    : [];

$percentage =
    max(
        0,
        min(
            100,
            (int) (
                $match['percentage']
                ?? 0
            )
        )
    );

$matched =
    max(
        0,
        (int) (
            $match['matched']
            ?? 0
        )
    );

$total =
    max(
        0,
        (int) (
            $match['total']
            ?? 0
        )
    );

$displayItems =
    isset(
        $match['displayItems']
    )
    && is_array(
        $match['displayItems']
    )
    ? $match['displayItems']
    : [];
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-dialog-centered
            modal-lg">

        <div class="modal-content">

            <div
                class="modal-header
                    bg-info-subtle
                    py-2">

                <div>

                    <h2
                        class="modal-title
                            fs-17">

                        Partner Preference Match

                    </h2>

                    <p
                        class="text-muted
                            fs-12 mb-0">

                        See how this profile matches
                        the member's Partner Preferences.

                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <div class="p-3 pb-0">

                <div
                    class="border
                        rounded-3
                        bg-warning-subtle
                        p-3
                        mb-3">

                    <div
                        class="d-flex
                            align-items-center
                            justify-content-between
                            flex-wrap
                            gap-3">

                        <div>

                            <div
                                class="fs-16
                                    fw-semibold">

                                <?= esc($name) ?>

                            </div>

                            <div
                                class="text-muted
                                    fs-12 mt-1">

                                Profile ID:

                                <strong class="text-body">

                                    <?= esc(
                                        $reference !== ''
                                            ? $reference
                                            : '-'
                                    ) ?>

                                </strong>

                            </div>

                        </div>

                        <div class="text-end">

                            <div
                                class="fs-20
                                    fw-bold
                                    text-primary">

                                <?= esc(
                                    (string) $matched
                                ) ?>/<?= esc(
                                            (string) $total
                                        ) ?>

                            </div>

                            <div
                                class="color-pink
                                    fs-12">

                                preferences matched

                            </div>

                        </div>

                    </div>

                </div>

                <div
                    class="d-flex
                        align-items-center
                        justify-content-between
                        flex-wrap
                        gap-2
                        mb-2">

                    <div class="fw-semibold">

                        Overall Match

                        <span
                            class="text-primary
                                ms-1">

                            <?= esc(
                                (string) $percentage
                            ) ?>%

                        </span>

                    </div>

                    <div
                        class="d-flex
                            align-items-center
                            gap-1
                            text-success
                            fs-13">

                        <i
                            class="ri-checkbox-circle-fill"
                            aria-hidden="true"></i>

                        <?= esc(
                            (string) $matched
                        ) ?>

                        matched

                    </div>

                </div>

                <div
                    class="progress mb-3"
                    role="progressbar"
                    aria-label="Partner preference match"
                    aria-valuenow="<?= esc(
                                        (string) $percentage,
                                        'attr'
                                    ) ?>"
                    aria-valuemin="0"
                    aria-valuemax="100">

                    <div
                        class="progress-bar"
                        style="<?= esc(
                                    'width: '
                                        . $percentage
                                        . '%;',
                                    'attr'
                                ) ?>">
                    </div>

                </div>

                <div
                    class="d-flex
                        align-items-center
                        justify-content-between
                        gap-2
                        bg-primary-subtle
                        rounded-3
                        p-3">

                    <span class="fw-semibold">
                        Partner Preferences
                    </span>

                    <span
                        class="text-muted
                            fs-13">

                        Match

                    </span>

                </div>

            </div>

            <div
                class="overflow-y-auto
                    p-3 pt-0"
                style="max-height: 45vh;">

                <?php if (
                    $displayItems === []
                ): ?>

                    <div
                        class="text-center
                            text-muted
                            py-4">

                        Partner Preference details
                        are not available.

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $displayItems
                        as $item
                    ): ?>

                        <?php
                        if (!is_array($item)) {
                            continue;
                        }

                        $title = trim(
                            (string) (
                                $item['title']
                                ?? ''
                            )
                        );

                        $value = trim(
                            (string) (
                                $item['value']
                                ?? ''
                            )
                        );

                        if ($title === '') {
                            continue;
                        }

                        $isMatched =
                            (
                                $item['matched']
                                ?? false
                            ) === true;

                        $isCompulsory =
                            (
                                $item['isCompulsory']
                                ?? false
                            ) === true;
                        ?>

                        <div
                            class="row
                                g-2
                                align-items-center
                                p-3
                                border-bottom">

                            <div
                                class="col-12
                                    col-md-4">

                                <div class="fs-14">

                                    <?= esc(
                                        $title
                                    ) ?>

                                </div>

                                <?php if (
                                    $isCompulsory
                                ): ?>

                                    <span
                                        class="badge
                                            bg-danger-subtle
                                            text-danger
                                            mt-1">

                                        Must Match

                                    </span>

                                <?php endif; ?>

                            </div>

                            <div
                                class="col-10
                                    col-md-6">

                                <div
                                    class="fw-medium
                                        fs-13">

                                    <?= esc(
                                        $value !== ''
                                            ? $value
                                            : 'Preference selected'
                                    ) ?>

                                </div>

                            </div>

                            <div
                                class="col-2
                                    text-end">

                                <?php if (
                                    $isMatched
                                ): ?>

                                    <i
                                        class="ri-checkbox-circle-fill
                                            text-success
                                            fs-20"
                                        aria-label="Matched"
                                        title="Matched">
                                    </i>

                                <?php else: ?>

                                    <i
                                        class="ri-close-circle-line
                                            text-warning
                                            fs-20"
                                        aria-label="Does not match"
                                        title="Does not match">
                                    </i>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Close

                </button>

            </div>

        </div>

    </div>

</div>