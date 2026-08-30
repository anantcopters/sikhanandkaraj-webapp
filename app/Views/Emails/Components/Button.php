<?php

declare(strict_types=1);

/**
 * @var string $url
 * @var string $label
 */

$resolvedUrl =
    trim((string) ($url ?? ''));

$resolvedLabel =
    trim((string) ($label ?? ''));

if (
    $resolvedUrl === ''
    || $resolvedLabel === ''
) {
    return;
}
?>

<table
    role="presentation"
    cellspacing="0"
    cellpadding="0"
    border="0"
    align="center"
    style="margin:28px auto;">

    <tr>
        <td
            align="center"
            bgcolor="#ce102c"
            style="
                border-radius:7px;
            ">

            <a
                href="<?= esc(
                            $resolvedUrl,
                            'attr'
                        ) ?>"
                style="
                    display:inline-block;
                    padding:13px 24px;
                    color:#ffffff;
                    text-decoration:none;
                    font-size:14px;
                    font-weight:600;
                    line-height:1.2;
                    border-radius:7px;
                ">

                <?= esc(
                    $resolvedLabel
                ) ?>

            </a>

        </td>
    </tr>

</table>