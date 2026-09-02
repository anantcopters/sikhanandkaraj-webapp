<?php

declare(strict_types=1);

/**
 * Member Support transactional email.
 *
 * Used by:
 *
 * - MEMBER_SUPPORT_REQUEST_RECEIVED
 * - MEMBER_SUPPORT_REQUEST_RESOLVED
 *
 * Privacy:
 *
 * - Do not reproduce the member's original support message.
 * - Do not expose administrator identity.
 * - Do not expose internal review notes.
 * - responseNote is the explicit member-facing administrator response only.
 *
 * @var string $userName
 * @var string $heading
 * @var string $message
 * @var string $requestReference
 * @var string $responseNote
 * @var string $actionUrl
 * @var string $actionLabel
 */

$resolvedName =
    trim(
        (string) (
            $userName
            ?? ''
        )
    );

if ($resolvedName === '') {
    $resolvedName =
        'Member';
}

$resolvedHeading =
    trim(
        (string) (
            $heading
            ?? ''
        )
    );

$resolvedMessage =
    trim(
        (string) (
            $message
            ?? ''
        )
    );

$resolvedReference =
    trim(
        (string) (
            $requestReference
            ?? ''
        )
    );

$resolvedResponseNote =
    trim(
        (string) (
            $responseNote
            ?? ''
        )
    );

$resolvedActionUrl =
    trim(
        (string) (
            $actionUrl
            ?? ''
        )
    );

$resolvedActionLabel =
    trim(
        (string) (
            $actionLabel
            ?? ''
        )
    );

ob_start();
?>

<p
    style="
        margin:0 0 18px;
        font-size:15px;
        line-height:1.7;
    ">
    Sat Sri Akal
    <?= esc($resolvedName) ?>,
</p>

<h2
    style="
        margin:0 0 14px;
        color:#310a57;
        font-size:20px;
        line-height:1.35;
    ">
    <?= esc($resolvedHeading) ?>
</h2>

<p
    style="
        margin:0 0 20px;
        font-size:15px;
        line-height:1.7;
    ">
    <?= esc($resolvedMessage) ?>
</p>

<?php if ($resolvedReference !== ''): ?>

    <!--
        The public SAKSUPP reference is safe to include.
        It helps the member correlate the email with Contact Us history.
    -->
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            margin:0 0 20px;
            background:#f9e9e7;
            border-radius:6px;
        ">

        <tr>
            <td
                style="
                    padding:14px 16px;
                    font-size:13px;
                    line-height:1.6;
                ">

                <span
                    style="
                        color:#6c757d;
                    ">
                    Support Request
                </span>

                <br>

                <strong
                    style="
                        color:#310a57;
                        font-size:15px;
                    ">
                    <?= esc($resolvedReference) ?>
                </strong>

            </td>
        </tr>

    </table>

<?php endif; ?>

<?php if ($resolvedResponseNote !== ''): ?>

    <!--
        This is the administrator's explicit member-facing response.
        Internal moderation/audit information must never be supplied here.
    -->
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            margin:0 0 20px;
            border-left:3px solid #310a57;
            background:#faf8fc;
        ">

        <tr>
            <td
                style="
                    padding:14px 16px;
                    color:#51475a;
                    font-size:14px;
                    line-height:1.7;
                ">

                <strong
                    style="
                        color:#310a57;
                    ">
                    Response from Support
                </strong>

                <br>

                <?= esc($resolvedResponseNote) ?>

            </td>
        </tr>

    </table>

<?php endif; ?>

<?= view(
    'Emails/Components/Button',
    [
        'url' =>
        $resolvedActionUrl,

        'label' =>
        $resolvedActionLabel,
    ]
) ?>

<p
    style="
        margin:20px 0 0;
        color:#6c757d;
        font-size:13px;
        line-height:1.6;
    ">
    You can securely review your support request
    and response history after signing in to
    Sikhanandkaraj.
</p>

<?php

$emailContent =
    (string) ob_get_clean();

echo view(
    'Emails/Layouts/Base',
    [
        'emailTitle' =>
        $resolvedHeading,

        'emailSubtitle' =>
        'Member Support',

        'emailContent' =>
        $emailContent,
    ]
);
