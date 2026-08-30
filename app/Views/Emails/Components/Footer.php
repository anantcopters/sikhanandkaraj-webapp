<?php

declare(strict_types=1);

$currentYear =
    date('Y');
?>

<tr>
    <td
        style="
            padding:24px 30px;
            background:#fff9e9;
            border-top:1px solid #eee5cb;
            text-align:center;
        ">

        <table
            role="presentation"
            width="100%"
            cellspacing="0"
            cellpadding="0"
            border="0">

            <tr>
                <td
                    style="
                        color:#310a57;
                        font-size:13px;
                        font-weight:600;
                        padding-bottom:7px;
                    ">
                    Secure & Trusted
                    &nbsp;&bull;&nbsp;
                    Family Oriented
                    &nbsp;&bull;&nbsp;
                    Verified Profiles
                </td>
            </tr>

            <tr>
                <td
                    style="
                        color:#6c757d;
                        font-size:12px;
                        line-height:1.6;
                    ">
                    SikhanandKaraj is built
                    to help Sikh families connect
                    through a trusted matrimonial
                    platform.
                </td>
            </tr>

            <tr>
                <td
                    style="
                        padding-top:15px;
                        color:#8b8490;
                        font-size:11px;
                    ">
                    © <?= esc(
                            $currentYear
                        ) ?>
                    SikhanandKaraj.
                    All rights reserved.
                </td>
            </tr>

        </table>

    </td>
</tr>