<?php

declare(strict_types=1);

/**
 * @var string $emailTitle
 * @var string $emailSubtitle
 * @var string $emailContent
 */

$resolvedTitle =
    trim((string) ($emailTitle ?? ''));

$resolvedSubtitle =
    trim((string) ($emailSubtitle ?? ''));

$resolvedContent =
    (string) ($emailContent ?? '');

$logoUrl =
    base_url(
        'assets/images/Logo.png'
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
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f7f5f8;
        color:#212529;
        font-family:
            'hkgrotesk',
            'Helvetica Neue',
            Arial,
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
                    ">

                    <?= view(
                        'Emails/Components/Header',
                        [
                            'logoUrl' =>
                            $logoUrl,

                            'subtitle' =>
                            $resolvedSubtitle,
                        ]
                    ) ?>

                    <tr>
                        <td
                            style="
                                padding:
                                    34px
                                    36px
                                    30px;
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