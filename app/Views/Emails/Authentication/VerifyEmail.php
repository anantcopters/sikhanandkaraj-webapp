<?php

declare(strict_types=1);

/**
 * @var string $userName
 * @var string $verificationUrl
 * @var int $expiresInHours
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify your email</title>
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
        margin: 0;
        padding: 0;
        background: #f7f4f5;
        color: #333333;
    ">

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0">

        <tr>
            <td
                align="center"
                style="padding: 32px 16px;">

                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        max-width: 600px;
                        background: #ffffff;
                        border-radius: 12px;
                        overflow: hidden;
                    ">

                    <tr>
                        <td
                            style="
                                padding: 24px 32px;
                                background: #ae1536;
                                color: #ffffff;
                            ">

                            <h1
                                style="
                                    margin: 0;
                                    font-size: 22px;
                                ">
                                Sikhanandkaraj
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px;">
                            <h2
                                style="
                                    margin: 0 0 16px;
                                    font-size: 20px;
                                ">
                                Verify your email address
                            </h2>

                            <p
                                style="
                                    margin: 0 0 16px;
                                    line-height: 1.6;
                                ">
                                Sat Sri Akal
                                <?= esc(
                                    $userName !== ''
                                        ? $userName
                                        : 'Member'
                                ) ?>,
                            </p>

                            <p
                                style="
                                    margin: 0 0 24px;
                                    line-height: 1.6;
                                ">
                                Please verify your email address by
                                clicking the button below.
                            </p>

                            <p
                                style="
                                    margin: 0 0 24px;
                                    text-align: center;
                                ">

                                <a
                                    href="<?= esc(
                                                $verificationUrl,
                                                'attr'
                                            ) ?>"
                                    style="
                                        display: inline-block;
                                        padding: 13px 24px;
                                        border-radius: 7px;
                                        background: #ae1536;
                                        color: #ffffff;
                                        font-weight: 600;
                                        text-decoration: none;
                                    ">
                                    Verify Email
                                </a>
                            </p>

                            <p
                                style="
                                    margin: 0 0 12px;
                                    color: #6c757d;
                                    font-size: 14px;
                                    line-height: 1.5;
                                ">
                                This link will expire in
                                <?= esc(
                                    (string) $expiresInHours
                                ) ?>
                                hours.
                            </p>

                            <p
                                style="
                                    margin: 0;
                                    color: #6c757d;
                                    font-size: 14px;
                                    line-height: 1.5;
                                ">
                                If you did not request this email,
                                you can safely ignore it.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>