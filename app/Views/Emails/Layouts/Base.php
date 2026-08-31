<?php

declare(strict_types=1);

/**
 * @var string $emailTitle
 * @var string $emailSubtitle
 * @var string $emailContent
 * @var string|null $supportPhone
 */

$resolvedTitle =
    trim(
        (string) ($emailTitle ?? '')
    );

$resolvedSubtitle =
    trim(
        (string) ($emailSubtitle ?? '')
    );

$resolvedContent =
    (string) ($emailContent ?? '');

$resolvedSupportPhone =
    trim(
        (string) ($supportPhone ?? '')
    );

$logoUrl =
    base_url(
        'assets/images/'
            . 'logo_sak_bgremove_final.png'
    );

$interRegularUrl =
    base_url(
        'assets/fonts/inter/'
            . 'Inter-Regular.ttf'
    );

$interMediumUrl =
    base_url(
        'assets/fonts/inter/'
            . 'Inter-Medium.ttf'
    );

$interSemiBoldUrl =
    base_url(
        'assets/fonts/inter/'
            . 'Inter-SemiBold.ttf'
    );

$interBoldUrl =
    base_url(
        'assets/fonts/inter/'
            . 'Inter-Bold.ttf'
    );
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        <?= esc($resolvedTitle) ?>
    </title>

    <style>
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 400;
            src: url('<?= esc(
                            $interRegularUrl,
                            'attr'
                        ) ?>') format('truetype');
        }

        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 500;
            src: url('<?= esc(
                            $interMediumUrl,
                            'attr'
                        ) ?>') format('truetype');
        }

        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 600;
            src: url('<?= esc(
                            $interSemiBoldUrl,
                            'attr'
                        ) ?>') format('truetype');
        }

        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 700;
            src: url('<?= esc(
                            $interBoldUrl,
                            'attr'
                        ) ?>') format('truetype');
        }

        body,
        table,
        td,
        a,
        p,
        div,
        span {
            font-family:
                'Inter',
                Arial,
                Helvetica,
                sans-serif;
        }
    </style>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f7f5f8;
        color:#212529;
        font-family:
            'Inter',
            Arial,
            Helvetica,
            sans-serif;
    ">

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            width:100%;
            background:#f7f5f8;
        ">

        <tr>
            <td
                align="center"
                style="padding:28px 14px;">

                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        width:100%;
                        max-width:620px;
                        background:#ffffff;
                        border-radius:12px;
                        overflow:hidden;
                        border:1px solid #ece7ef;
                        font-family:
                            'Inter',
                            Arial,
                            Helvetica,
                            sans-serif;
                    ">

                    <?= view(
                        'Emails/Components/Header',
                        [
                            'logoUrl' =>
                            $logoUrl,

                            'subtitle' =>
                            $resolvedSubtitle,

                            'supportPhone' =>
                            $resolvedSupportPhone,
                        ]
                    ) ?>

                    <tr>
                        <td
                            style="
                                padding:
                                    34px
                                    36px
                                    30px;
                                font-family:
                                    'Inter',
                                    Arial,
                                    Helvetica,
                                    sans-serif;
                            ">

                            <?= $resolvedContent ?>

                        </td>
                    </tr>

                    <?= view(
                        'Emails/Components/Footer'
                    ) ?>

                </table>

            </td>
        </tr>

    </table>

</body>

</html>