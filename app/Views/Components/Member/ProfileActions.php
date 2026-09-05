<?php

declare(strict_types=1);

/**
 * Shared actions for another member's profile.
 *
 * Used by:
 *
 * - Pages/Profile/View.php
 * - Components/Member/ProfileCard.php
 *
 * Business decisions are resolved before this component.
 *
 * @var string $profileReference
 * @var bool   $isShortlisted
 * @var bool   $canShortlist
 * @var bool   $canReport
 * @var bool   $canBlock
 * @var bool   $hasReportedProfile
 * @var string $reportedProfileStatusLabel
 * @var string $shortlistUrl
 * @var string $reportModalId
 * @var string $blockModalId
 */

$profileReference = trim(
    (string) (
        $profileReference
        ?? ''
    )
);

$messageUrl =
    $profileReference !== ''
    ? route_to(
        'web.members.message',
        $profileReference
    )
    : '';

$isShortlisted =
    ($isShortlisted ?? false)
    === true;

$canShortlist =
    ($canShortlist ?? false)
    === true;

$canReport =
    ($canReport ?? false)
    === true;

$canBlock =
    ($canBlock ?? false)
    === true;

$hasReportedProfile =
    ($hasReportedProfile ?? false)
    === true;

$reportedProfileStatusLabel = trim(
    (string) (
        $reportedProfileStatusLabel
        ?? ''
    )
);

$shortlistUrl = trim(
    (string) (
        $shortlistUrl
        ?? ''
    )
);

$reportModalId = trim(
    (string) (
        $reportModalId
        ?? 'memberReportModal'
    )
);

$blockModalId = trim(
    (string) (
        $blockModalId
        ?? 'memberBlockModal'
    )
);

$hasShortlistAction =
    $shortlistUrl !== ''
    && (
        $canShortlist
        || $isShortlisted
    );

$hasActions =
    $messageUrl !== ''
    || $hasShortlistAction
    || $canReport
    || $canBlock;

if (
    $profileReference === ''
    || !$hasActions
) {
    return;
}
?>

<div class="dropdown">

    <button
        type="button"
        class="btn btn-info btn-icon"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false"
        aria-label="Profile actions">

        <i
            class="ri-more-2-fill fs-18"
            aria-hidden="true">
        </i>

    </button>

    <div
        class="
            dropdown-menu
            dropdown-menu-end
            p-2
            border
            border-danger
            border-opacity-50
            shadow-md
        "
        style="min-width: 220px;">
        <?php if ($messageUrl !== ''): ?>

            <a
                href="<?= esc(
                            $messageUrl,
                            'attr'
                        ) ?>"
                class="
            dropdown-item
            rounded
            d-flex
            align-items-center
            gap-2
        ">

                <i
                    class="ri-message-3-line
                text-primary"
                    aria-hidden="true">
                </i>

                Message

            </a>

        <?php endif; ?>
        <?php if ($hasShortlistAction): ?>

            <form
                method="post"
                action="<?= esc(
                            $shortlistUrl,
                            'attr'
                        ) ?>"
                data-member-shortlist-form>

                <?= csrf_field() ?>

                <button
                    type="submit"
                    class="
                        dropdown-item
                        rounded
                        d-flex
                        align-items-center
                        gap-2
                    "
                    data-member-shortlist-submit>

                    <span
                        class="
                            d-inline-flex
                            align-items-center
                            gap-2
                        "
                        data-member-shortlist-label>

                        <i
                            class="<?= $isShortlisted
                                        ? 'ri-bookmark-fill'
                                        : 'ri-bookmark-line' ?>"
                            aria-hidden="true">
                        </i>

                        <?= $isShortlisted
                            ? 'Remove from Shortlist'
                            : 'Shortlist Profile' ?>

                    </span>

                    <span
                        class="
                            d-none
                            align-items-center
                            gap-1
                        "
                        data-member-shortlist-loading>

                        <span
                            class="
                                spinner-border
                                spinner-border-sm
                            "
                            aria-hidden="true">
                        </span>

                        Saving...

                    </span>

                </button>

            </form>

        <?php endif; ?>

        <?php if ($canReport): ?>

            <?php if ($hasReportedProfile): ?>

                <button
                    type="button"
                    class="
                        dropdown-item
                        rounded
                        d-flex
                        align-items-center
                        gap-2
                        text-muted
                    "
                    disabled>

                    <i
                        class="ri-flag-fill"
                        aria-hidden="true">
                    </i>

                    Reported:
                    <?= esc(
                        $reportedProfileStatusLabel
                    ) ?>

                </button>

            <?php else: ?>

                <button
                    type="button"
                    class="
                        dropdown-item
                        rounded
                        d-flex
                        align-items-center
                        gap-2
                    "
                    data-bs-toggle="modal"
                    data-bs-target="#<?= esc(
                                            $reportModalId,
                                            'attr'
                                        ) ?>">

                    <i
                        class="
                            ri-flag-line
                            text-warning
                        "
                        aria-hidden="true">
                    </i>

                    Report Profile

                </button>

            <?php endif; ?>

        <?php endif; ?>

        <?php if (
            $canBlock
            && (
                $hasShortlistAction
                || $canReport
            )
        ): ?>

            <div class="dropdown-divider"></div>

        <?php endif; ?>

        <?php if ($canBlock): ?>

            <button
                type="button"
                class="
                    dropdown-item
                    rounded
                    d-flex
                    align-items-center
                    gap-2
                    text-danger
                "
                data-bs-toggle="modal"
                data-bs-target="#<?= esc(
                                        $blockModalId,
                                        'attr'
                                    ) ?>">

                <i
                    class="ri-forbid-line"
                    aria-hidden="true">
                </i>

                Block Profile

            </button>

        <?php endif; ?>

    </div>

</div>