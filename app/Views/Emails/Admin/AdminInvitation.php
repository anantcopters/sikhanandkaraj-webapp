<?php

declare(strict_types=1);

/** @var string $adminName */
/** @var string $invitationUrl */
/** @var int|string $expiresInHours */
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Administrator Invitation</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
        );

        body {
            font-family: 'Inter', Arial, Helvetica, sans-serif;
        }
    </style>
</head>

<body style="
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
                                SikhAnandKaraj
                            </h1>

                            <p
                                style="
                                    margin:6px 0 0;
                                    opacity:.9;
                                ">
                                Administrator Invitation
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p>
                                Sat Sri Akal <?= esc($adminName) ?>,
                            </p>

                            <p>
                                You have been invited to become an
                                administrator for SikhAnandKaraj.
                            </p>

                            <p>
                                Use the button below to verify your
                                email address and create your password.
                            </p>

                            <p style="margin:32px 0;">
                                <a
                                    href="<?= esc(
                                                $invitationUrl,
                                                'attr'
                                            ) ?>"
                                    style="
                                        display:inline-block;
                                        background:#ae1536;
                                        color:#ffffff;
                                        text-decoration:none;
                                        padding:14px 24px;
                                        border-radius:8px;
                                        font-weight:bold;
                                    ">
                                    Verify and Create Password
                                </a>
                            </p>

                            <p>
                                This link is valid for
                                <?= esc($expiresInHours) ?> hours
                                and can be used only once.
                            </p>

                            <p
                                style="
                                    color:#71717a;
                                    font-size:13px;
                                ">
                                If you were not expecting this
                                invitation, you may safely ignore
                                this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>