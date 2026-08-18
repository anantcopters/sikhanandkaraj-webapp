<?php

declare(strict_types=1);

/**
 * @var string $userName
 * @var string $emailAddress
 * @var string $verificationUrl
 * @var int    $expiresInHours
 * @var bool   $isReplacement
 */

$resolvedName = trim(
    (string) ($userName ?? '')
);

if ($resolvedName === '') {
    $resolvedName = 'Member';
}

$resolvedEmail = trim(
    (string) ($emailAddress ?? '')
);

$isReplacement =
    ($isReplacement ?? false) === true;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Verify Your Email Address</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
        );

        body {
            font-family: 'Inter', Arial, Helvetica, sans-serif;
        }
    </style>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f6f2f3;
        color:#27272a;
    ">

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="background:#f6f2f3;padding:32px 16px;">

        <tr>
            <td align="center">

                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    style="
                        max-width:600px;
                        background:#ffffff;
                        border-radius:16px;
                        overflow:hidden;
                    ">

                    <tr>
                        <td
                            style="
                                background:#ae1536;
                                padding:24px 32px;
                                color:#ffffff;
                            ">

                            <h1
                                style="
                                    margin:0;
                                    font-size:22px;
                                ">

                                Sikhanandkaraj
                            </h1>

                            <p
                                style="
                                    margin:6px 0 0;
                                    opacity:.9;
                                ">

                                Email Verification
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">

                            <p
                                style="
                                    margin:0 0 16px;
                                    line-height:1.6;
                                ">

                                Sat Sri Akal
                                <?= esc($resolvedName) ?>,
                            </p>

                            <p
                                style="
                                    margin:0 0 16px;
                                    line-height:1.6;
                                ">

                                Please confirm that
                                <strong>
                                    <?= esc($resolvedEmail) ?>
                                </strong>
                                should be linked with your
                                Sikhanandkaraj account.
                            </p>

                            <?php if ($isReplacement): ?>
                                <p
                                    style="
                                        margin:0 0 16px;
                                        line-height:1.6;
                                    ">

                                    Your current verified email will
                                    remain active until this new address
                                    is confirmed.
                                </p>
                            <?php endif; ?>

                            <p
                                style="
                                    margin:32px 0;
                                    text-align:center;
                                ">

                                <a
                                    href="<?= esc(
                                                $verificationUrl,
                                                'attr'
                                            ) ?>"
                                    style="
                                        display:inline-block;
                                        background:#ae1536;
                                        color:#ffffff;
                                        text-decoration:none;
                                        padding:14px 24px;
                                        border-radius:8px;
                                        font-weight:600;
                                    ">

                                    Verify Email Address
                                </a>
                            </p>

                            <p
                                style="
                                    margin:0 0 12px;
                                    color:#71717a;
                                    font-size:13px;
                                    line-height:1.6;
                                ">

                                This secure link is valid for
                                <?= esc(
                                    (string) $expiresInHours
                                ) ?>
                                hours and can be used only once.
                            </p>

                            <p
                                style="
                                    margin:0;
                                    color:#71717a;
                                    font-size:13px;
                                    line-height:1.6;
                                ">

                                If you did not request this change,
                                you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>