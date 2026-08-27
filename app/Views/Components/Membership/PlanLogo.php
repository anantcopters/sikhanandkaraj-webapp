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
    'plan_go_short_removebg.png',

    'PLUS' =>
    'plan_plus_short_removebg.png',

    'PRO' =>
    'plan_pro_short_removebg.png',
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
    class="img-fluid"
    width="<?= esc(
                (string) $width,
                'attr'
            ) ?>"
    loading="lazy">