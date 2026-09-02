<?php

declare(strict_types=1);

/**
 * @var string $logoUrl
 * @var string $subtitle
 * @var string $supportPhone
 */

$resolvedLogo =
    trim(
        (string) ($logoUrl ?? '')
    );

$resolvedSubtitle =
    trim(
        (string) ($subtitle ?? '')
    );

$resolvedSupportPhone =
    '+91 98877 11226';
?>

<tr>
    <td
        style="
            padding:
                22px
                28px;
            background:#f9e9e7;
            font-family:
                'Inter',
                Arial,
                Helvetica,
                sans-serif;
            padding-bottom: 10px;
        ">

        <table
            role="presentation"
            width="100%"
            cellspacing="0"
            cellpadding="0"
            border="0"
            style="
                width:100%;
                border-collapse:collapse;
                font-family:
                    'Inter',
                    Arial,
                    Helvetica,
                    sans-serif;
            ">

            <tr>

                <!-- Brand logo -->
                <td
                    align="left"
                    valign="middle"
                    style="
                        vertical-align:middle;
                    ">

                    <?php if (
                        $resolvedLogo !== ''
                    ): ?>

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
                                margin:0;
                                border:0;
                                outline:none;
                                text-decoration:none;
                            ">

                    <?php else: ?>

                        <div
                            style="
                                color:#310a57;
                                font-family:
                                    'Inter',
                                    Arial,
                                    Helvetica,
                                    sans-serif;
                                font-size:23px;
                                line-height:1.2;
                                font-weight:700;
                            ">
                            Sikhanandkaraj
                        </div>

                    <?php endif; ?>

                </td>

                <!-- Support phone -->
                <?php if (
                    $resolvedSupportPhone !== ''
                ): ?>

                    <td
                        align="right"
                        valign="middle"
                        style="
                            vertical-align:middle;
                            white-space:nowrap;
                            padding-left:20px;
                        ">

                        <table
                            role="presentation"
                            cellspacing="0"
                            cellpadding="0"
                            border="0"
                            align="right"
                            style="
                                border-collapse:collapse;
                            ">

                            <tr>

                                <td
                                    align="center"
                                    valign="middle"
                                    width="34"
                                    height="34"
                                    style="
                                        width:34px;
                                        height:34px;
                                        background:#f4cfcb;
                                        border-radius:50%;
                                        color:#ce102c;
                                        font-size:17px;
                                        line-height:34px;
                                        text-align:center;
                                        vertical-align:middle;
                                    ">

                                    &#9742;

                                </td>

                                <td
                                    valign="middle"
                                    style="
                                        padding-left:9px;
                                        color:#212529;
                                        font-family:
                                            'Inter',
                                            Arial,
                                            Helvetica,
                                            sans-serif;
                                        font-size:13px;
                                        line-height:1.4;
                                        font-weight:500;
                                        white-space:nowrap;
                                        vertical-align:middle;
                                    ">

                                    <?= esc(
                                        $resolvedSupportPhone
                                    ) ?>

                                </td>

                            </tr>

                        </table>

                    </td>

                <?php endif; ?>

            </tr>

            <?php if (
                $resolvedSubtitle !== ''
            ): ?>

                <tr>
                    <td
                        colspan="2"
                        align="middle"
                        style="
                            padding-top:15px;
                            color:#6c757d;
                            font-family:
                                'Inter',
                                Arial,
                                Helvetica,
                                sans-serif;
                            font-size:14px;
                            line-height:1.5;
                            font-weight:600;
                        ">

                        <?= esc(
                            $resolvedSubtitle
                        ) ?>

                    </td>
                </tr>

            <?php endif; ?>

        </table>

    </td>
</tr>