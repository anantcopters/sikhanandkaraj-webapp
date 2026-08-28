<?php

declare(strict_types=1);

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
    'Logo_Go_Plan.png',

    'PLUS' =>
    'Logo_Plus_Plan.png',

    'PRO' =>
    'Logo_Pro_Plan.png',
];

$image =
    $logos[$planCode]
    ?? '';

if ($image === '') {
    return;
}
?>

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