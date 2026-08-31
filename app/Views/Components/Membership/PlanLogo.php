<?php

declare(strict_types=1);

/**
 * Shared membership-plan identity component.
 *
 * Paid memberships render their branded plan logo.
 * Any missing or unsupported plan code represents a Free Account.
 *
 * @var string|null $planCode
 * @var int|null    $width
 */

$planCode =
    mb_strtoupper(
        trim(
            (string) (
                $planCode
                ?? ''
            )
        )
    );

$width =
    max(
        1,
        (int) (
            $width
            ?? 120
        )
    );

$logos = [
    'GO' =>
    'Logo_Go_Plan_S.png',

    'PLUS' =>
    'Logo_Plus_Plan_S.png',

    'PRO' =>
    'Logo_Pro_Plan_S.png',
];

$image =
    $logos[$planCode]
    ?? '';

?>

<?php if ($image !== ''): ?>

    <img
        src="<?= base_url(
                    'assets/images/'
                        . $image
                ) ?>"
        alt="<?= esc(
                    'Sikhanandkaraj '
                        . $planCode,
                    'attr'
                ) ?>"
        class="img-fluid my-2"
        width="<?= esc(
                    (string) $width,
                    'attr'
                ) ?>"
        loading="lazy">

<?php else: ?>

    <span
        class="badge rounded
            bg-primary-subtle
            text-primary
            border border-primary
            border-opacity-25
            p-1 fs-12 fw-normal mb-2 mt-2">

        <i
            class="ri-vip-crown-line
                me-1 fs-12"
            aria-hidden="true">
        </i>

        Free Account

    </span>

<?php endif; ?>