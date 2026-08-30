<?php

declare(strict_types=1);

/**
 * @var string $logoUrl
 * @var string $subtitle
 */

$resolvedLogo =
    trim((string) ($logoUrl ?? ''));

$resolvedSubtitle =
    trim((string) ($subtitle ?? ''));
?>

<tr>
    <td
        align="center"
        style="
            padding:
                22px
                28px
                18px;
            background:#ffffff;
            border-bottom:
                3px solid #ce102c;
        ">

        <?php if ($resolvedLogo !== ''): ?>

            <img
                src="<?= esc(
                            $resolvedLogo,
                            'attr'
                        ) ?>"
                alt="Sikhanandkaraj"
                width="210"
                style="
                    display:block;
                    width:210px;
                    max-width:100%;
                    height:auto;
                    margin:0 auto;
                    border:0;
                ">

        <?php else: ?>

            <div
                style="
                    color:#310a57;
                    font-size:24px;
                    line-height:1.2;
                    font-weight:700;
                ">
                Sikhanandkaraj
            </div>

        <?php endif; ?>

        <?php if (
            $resolvedSubtitle !== ''
        ): ?>

            <div
                style="
                    margin-top:8px;
                    color:#6c757d;
                    font-size:13px;
                    line-height:1.4;
                ">
                <?= esc(
                    $resolvedSubtitle
                ) ?>
            </div>

        <?php endif; ?>

    </td>
</tr>